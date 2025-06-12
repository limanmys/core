<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class KubernetesController extends Controller
{
    public function getNamespaces(Request $request)
    {
        return $this->makeRenderEngineRequest($request, 'namespaces');
    }

    public function getDeployments(Request $request)
    {
        return $this->makeRenderEngineRequest($request, 'deployments', [
            'namespace' => $request->input('namespace'),
        ]);
    }

    public function getDeploymentDetails(Request $request)
    {
        return $this->makeRenderEngineRequest($request, 'deploymentDetails', [
            'namespace' => $request->input('namespace'),
            'deployment' => $request->input('deployment'),
        ]);
    }

    public function getDeploymentDetailsFromServer(Request $request)
    {
        if (server()->os !== 'kubernetes') {
            return response()->json(['error' => 'Server is not a Kubernetes server'], 400);
        }

        $information = server()->kubernetesInformation()->first();

        if (!$information) {
            return response()->json(['error' => 'Kubernetes information not found for this server'], 404);
        }

        return $this->makeRenderEngineRequest($request, 'deploymentDetails', [
            'kubeconfig' => $information->kubeconfig,
            ...$information->toArray(),
        ]);
    }

    public function getReachableIpFromDeploymentDetails(Request $request)
    {
        $response = $this->makeRenderEngineRequest($request, 'deploymentDetails', [
            'namespace' => $request->input('namespace'),
            'deployment' => $request->input('deployment'),
        ], true);

        if (!$response || (!is_array($response) && !is_object($response))) {
            return response()->json(['error' => 'Failed to get deployment details'], 404);
        }

        // Convert stdClass to array for easier processing
        $response = json_decode(json_encode($response), true);

        $reachableEndpoints = [];
        $deploymentName = $request->input('deployment');
        $namespace = $request->input('namespace');

        // Strategy 1: Check for public endpoints in annotations
        $publicEndpoints = $this->extractPublicEndpoints($response);
        if (!empty($publicEndpoints)) {
            $reachableEndpoints = array_merge($reachableEndpoints, $publicEndpoints);
        }

        // Strategy 2: Check for LoadBalancer or NodePort services
        $serviceEndpoints = $this->extractServiceEndpoints($response, $deploymentName, $namespace);
        if (!empty($serviceEndpoints)) {
            $reachableEndpoints = array_merge($reachableEndpoints, $serviceEndpoints);
        }

        // Strategy 3: Check for ingress hostnames
        $ingressEndpoints = $this->extractIngressEndpoints($response);
        if (!empty($ingressEndpoints)) {
            $reachableEndpoints = array_merge($reachableEndpoints, $ingressEndpoints);
        }

        // Strategy 4: Fallback to cluster-internal service discovery
        $internalEndpoints = $this->extractInternalEndpoints($response, $deploymentName, $namespace);
        if (!empty($internalEndpoints)) {
            $reachableEndpoints = array_merge($reachableEndpoints, $internalEndpoints);
        }

        // Remove duplicates and sort by priority
        $reachableEndpoints = $this->prioritizeEndpoints($reachableEndpoints);

        // Test endpoint connectivity and find the best accessible option
        $testedEndpoints = $this->testEndpointConnectivity($reachableEndpoints);
        $bestEndpoint = $this->findBestAccessibleEndpoint($testedEndpoints);

        return response()->json([
            'endpoints' => $testedEndpoints,
            'bestEndpoint' => $bestEndpoint,
            'count' => count($testedEndpoints),
            'deployment' => $deploymentName,
            'namespace' => $namespace
        ]);
    }

    /**
     * Extract public endpoints from field.cattle.io/publicEndpoints annotation
     */
    private function extractPublicEndpoints(array $deploymentData): array
    {
        $endpoints = [];
        
        // Look for annotations in deployment metadata
        $annotations = $deploymentData['metadata']['annotations'] ?? [];
        
        if (isset($annotations['field.cattle.io/publicEndpoints'])) {
            $publicEndpoints = json_decode($annotations['field.cattle.io/publicEndpoints'], true);
            
            if (is_array($publicEndpoints)) {
                foreach ($publicEndpoints as $endpoint) {
                    $addresses = $endpoint['addresses'] ?? [];
                    $port = $endpoint['port'] ?? null;
                    $protocol = strtolower($endpoint['protocol'] ?? 'tcp');
                    $hostname = $endpoint['hostname'] ?? null;
                    $path = $endpoint['path'] ?? '/';
                    
                    foreach ($addresses as $address) {
                        $endpoints[] = [
                            'type' => 'public',
                            'address' => $address,
                            'hostname' => $hostname,
                            'port' => $port,
                            'protocol' => $protocol,
                            'path' => $path,
                            'priority' => 1, // Highest priority
                            'url' => $this->buildUrl($hostname ?: $address, $port, $protocol, $path)
                        ];
                    }
                }
            }
        }
        
        return $endpoints;
    }

    /**
     * Extract service endpoints (LoadBalancer, NodePort)
     */
    private function extractServiceEndpoints(array $deploymentData, string $deploymentName, string $namespace): array
    {
        $endpoints = [];
        
        // Look for service information in the deployment data
        if (isset($deploymentData['services'])) {
            foreach ($deploymentData['services'] as $service) {
                $serviceType = $service['spec']['type'] ?? 'ClusterIP';
                
                if (in_array($serviceType, ['LoadBalancer', 'NodePort'])) {
                    $ports = $service['spec']['ports'] ?? [];
                    
                    foreach ($ports as $portInfo) {
                        $port = $portInfo['port'] ?? null;
                        $nodePort = $portInfo['nodePort'] ?? null;
                        $protocol = strtolower($portInfo['protocol'] ?? 'tcp');
                        
                        // For LoadBalancer services
                        if ($serviceType === 'LoadBalancer') {
                            $ingress = $service['status']['loadBalancer']['ingress'] ?? [];
                            foreach ($ingress as $ingressInfo) {
                                $address = $ingressInfo['ip'] ?? $ingressInfo['hostname'] ?? null;
                                if ($address) {
                                    $endpoints[] = [
                                        'type' => 'loadbalancer',
                                        'address' => $address,
                                        'hostname' => is_string($ingressInfo['hostname'] ?? null) ? $ingressInfo['hostname'] : null,
                                        'port' => $port,
                                        'protocol' => $protocol,
                                        'priority' => 2,
                                        'url' => $this->buildUrl($address, $port, $protocol)
                                    ];
                                }
                            }
                        }
                        
                        // For NodePort services
                        if ($serviceType === 'NodePort' && $nodePort) {
                            // Try to get node IPs from deployment data
                            $nodeIps = $this->extractNodeIps($deploymentData);
                            foreach ($nodeIps as $nodeIp) {
                                $endpoints[] = [
                                    'type' => 'nodeport',
                                    'address' => $nodeIp,
                                    'port' => $nodePort,
                                    'protocol' => $protocol,
                                    'priority' => 3,
                                    'url' => $this->buildUrl($nodeIp, $nodePort, $protocol)
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        return $endpoints;
    }

    /**
     * Extract ingress endpoints
     */
    private function extractIngressEndpoints(array $deploymentData): array
    {
        $endpoints = [];
        
        if (isset($deploymentData['ingresses'])) {
            foreach ($deploymentData['ingresses'] as $ingress) {
                $rules = $ingress['spec']['rules'] ?? [];
                
                foreach ($rules as $rule) {
                    $host = $rule['host'] ?? null;
                    $paths = $rule['http']['paths'] ?? [];
                    
                    foreach ($paths as $pathInfo) {
                        $path = $pathInfo['path'] ?? '/';
                        $servicePort = $pathInfo['backend']['service']['port']['number'] ?? 80;
                        
                        if ($host) {
                            $protocol = 'http';
                            // Check if TLS is configured
                            if (isset($ingress['spec']['tls'])) {
                                foreach ($ingress['spec']['tls'] as $tls) {
                                    if (in_array($host, $tls['hosts'] ?? [])) {
                                        $protocol = 'https';
                                        break;
                                    }
                                }
                            }
                            
                            $endpoints[] = [
                                'type' => 'ingress',
                                'address' => $host,
                                'hostname' => $host,
                                'port' => $protocol === 'https' ? 443 : 80,
                                'protocol' => $protocol,
                                'path' => $path,
                                'priority' => 2,
                                'url' => $this->buildUrl($host, null, $protocol, $path)
                            ];
                        }
                    }
                }
            }
        }
        
        return $endpoints;
    }

    /**
     * Extract internal endpoints (ClusterIP services)
     */
    private function extractInternalEndpoints(array $deploymentData, string $deploymentName, string $namespace): array
    {
        $endpoints = [];
        
        // Try deployment name as service name (common convention)
        $serviceName = $deploymentName;
        $clusterDomain = 'cluster.local'; // Default Kubernetes cluster domain
        
        // Look for ClusterIP services
        if (isset($deploymentData['services'])) {
            foreach ($deploymentData['services'] as $service) {
                $serviceType = $service['spec']['type'] ?? 'ClusterIP';
                $serviceName = $service['metadata']['name'] ?? $deploymentName;
                
                if ($serviceType === 'ClusterIP') {
                    $clusterIP = $service['spec']['clusterIP'] ?? null;
                    $ports = $service['spec']['ports'] ?? [];
                    
                    foreach ($ports as $portInfo) {
                        $port = $portInfo['port'] ?? null;
                        $protocol = strtolower($portInfo['protocol'] ?? 'tcp');
                        
                        // Internal service DNS name
                        $internalHostname = "{$serviceName}.{$namespace}.svc.{$clusterDomain}";
                        
                        if ($clusterIP && $clusterIP !== 'None') {
                            $endpoints[] = [
                                'type' => 'internal',
                                'address' => $clusterIP,
                                'hostname' => $internalHostname,
                                'port' => $port,
                                'protocol' => $protocol,
                                'priority' => 4,
                                'url' => $this->buildUrl($internalHostname, $port, $protocol)
                            ];
                        }
                    }
                }
            }
        }
        
        return $endpoints;
    }

    /**
     * Extract node IPs from deployment data
     */
    private function extractNodeIps(array $deploymentData): array
    {
        $nodeIps = [];
        
        // Look for node information in various places
        if (isset($deploymentData['nodes'])) {
            foreach ($deploymentData['nodes'] as $node) {
                $addresses = $node['status']['addresses'] ?? [];
                foreach ($addresses as $address) {
                    if ($address['type'] === 'ExternalIP' || $address['type'] === 'InternalIP') {
                        $nodeIps[] = $address['address'];
                    }
                }
            }
        }
        
        // Fallback: try to extract from pod status
        if (empty($nodeIps) && isset($deploymentData['pods'])) {
            foreach ($deploymentData['pods'] as $pod) {
                $hostIP = $pod['status']['hostIP'] ?? null;
                if ($hostIP && !in_array($hostIP, $nodeIps)) {
                    $nodeIps[] = $hostIP;
                }
            }
        }
        
        return array_unique($nodeIps);
    }

    /**
     * Build URL from components
     */
    private function buildUrl(?string $host, ?int $port, string $protocol, string $path = '/'): ?string
    {
        if (!$host) {
            return null;
        }
        
        $url = $protocol . '://' . $host;
        
        // Only add port if it's not the default for the protocol
        if ($port && !(($protocol === 'http' && $port === 80) || ($protocol === 'https' && $port === 443))) {
            $url .= ':' . $port;
        }
        
        if ($path && $path !== '/') {
            $url .= $path;
        }
        
        return $url;
    }

    /**
     * Prioritize and deduplicate endpoints
     */
    private function prioritizeEndpoints(array $endpoints): array
    {
        // Remove duplicates based on URL
        $unique = [];
        $seen = [];
        
        foreach ($endpoints as $endpoint) {
            $key = $endpoint['url'] ?? ($endpoint['address'] . ':' . $endpoint['port']);
            if (!in_array($key, $seen)) {
                $unique[] = $endpoint;
                $seen[] = $key;
            }
        }
        
        // Sort by priority (lower number = higher priority)
        usort($unique, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return $unique;
    }

    /**
     * Test endpoint connectivity
     */
    private function testEndpointConnectivity(array $endpoints): array
    {
        $client = new Client([
            'verify' => false,
            'timeout' => 5,
            'connect_timeout' => 3,
            'http_errors' => false
        ]);

        foreach ($endpoints as &$endpoint) {
            $endpoint['accessible'] = false;
            $endpoint['response_time'] = null;
            $endpoint['status_code'] = null;
            $endpoint['error'] = null;

            try {
                $startTime = microtime(true);
                
                // Test different connection methods based on protocol
                if (in_array($endpoint['protocol'], ['http', 'https'])) {
                    // Test HTTP/HTTPS endpoints
                    $response = $client->request('GET', $endpoint['url'], [
                        'timeout' => 5,
                        'connect_timeout' => 3
                    ]);
                    
                    $endpoint['status_code'] = $response->getStatusCode();
                    $endpoint['accessible'] = $response->getStatusCode() < 500; // Consider 4xx as accessible but with issues
                    
                } else {
                    // Test TCP connectivity for non-HTTP protocols
                    $endpoint['accessible'] = $this->testTcpConnection($endpoint['address'], $endpoint['port']);
                }
                
                $endpoint['response_time'] = round((microtime(true) - $startTime) * 1000, 2); // ms
                
            } catch (\Exception $e) {
                $endpoint['accessible'] = false;
                $endpoint['error'] = $e->getMessage();
                $endpoint['response_time'] = round((microtime(true) - $startTime) * 1000, 2);
            }
        }

        return $endpoints;
    }

    /**
     * Test TCP connection to host:port
     */
    private function testTcpConnection(string $host, int $port, int $timeout = 3): bool
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
        
        if ($connection) {
            fclose($connection);
            return true;
        }
        
        return false;
    }

    /**
     * Find the best accessible endpoint with domain preference
     */
    private function findBestAccessibleEndpoint(array $endpoints): ?array
    {
        // Filter only accessible endpoints
        $accessibleEndpoints = array_filter($endpoints, function($endpoint) {
            return $endpoint['accessible'] === true;
        });

        if (empty($accessibleEndpoints)) {
            return null;
        }

        // Sort by priority with domain preference
        usort($accessibleEndpoints, function($a, $b) {
            // First, check if both are accessible
            if ($a['accessible'] !== $b['accessible']) {
                return $b['accessible'] <=> $a['accessible']; // accessible first
            }

            // Prioritize endpoints with domain names (hostname that's not an IP)
            $aHasDomain = $this->hasDomainName($a['hostname'] ?? $a['address']);
            $bHasDomain = $this->hasDomainName($b['hostname'] ?? $b['address']);
            
            if ($aHasDomain !== $bHasDomain) {
                return $bHasDomain <=> $aHasDomain; // domain names first
            }

            // Then by original priority
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] <=> $b['priority']; // lower priority number = higher priority
            }

            // Then by response time (faster first)
            if ($a['response_time'] !== $b['response_time']) {
                return $a['response_time'] <=> $b['response_time'];
            }

            // Finally by protocol preference (https > http > others)
            $protocolPriority = ['https' => 1, 'http' => 2, 'tcp' => 3];
            $aPriority = $protocolPriority[$a['protocol']] ?? 4;
            $bPriority = $protocolPriority[$b['protocol']] ?? 4;
            
            return $aPriority <=> $bPriority;
        });

        return $accessibleEndpoints[0] ?? null;
    }

    /**
     * Check if a hostname is a domain name (not an IP address)
     */
    private function hasDomainName(?string $hostname): bool
    {
        if (!$hostname) {
            return false;
        }

        // Check if it's an IPv4 address
        if (filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        // Check if it's an IPv6 address
        if (filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        // Check if it contains a dot and looks like a domain
        if (strpos($hostname, '.') !== false && !is_numeric(str_replace('.', '', $hostname))) {
            return true;
        }

        return false;
    }

    /**
     * Make a request to the render engine kubernetes endpoint
     */
    private function makeRenderEngineRequest(Request $request, string $endpoint, array $payload = [], bool $returnRaw = false)
    {
        $client = new Client(['verify' => false, 'cookies' => true]);
        
        $payload = array_merge(['kubeconfig' => $request->input('kubeconfig')], $payload);
        
        $res = $client->request('POST', env('RENDER_ENGINE_ADDRESS', 'https://127.0.0.1:2806') . '/kubernetes/' . $endpoint, [
            'cookies' => convertToCookieJar($request, '127.0.0.1'),
            'timeout' => 30,
            'json' => $payload,
        ]);
        
        $output = (string) $res->getBody();
        $isJson = isJson($output, true);
        $statusCode = $res->getStatusCode();

        if ($returnRaw && $isJson) {
            return $isJson;
        } else if ($returnRaw) {
            return [];
        }
        
        if ($isJson) {
            $data = json_decode($output, true);
            return response()->json($data, $statusCode);
        } else {
            return response($output, $statusCode);
        }
    }
}
