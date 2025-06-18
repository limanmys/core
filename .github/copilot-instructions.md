# Liman MYS (Merkezi Yönetim Sistemi) - GitHub Copilot Instructions

## Proje Genel Bakış

Liman, sunucuları, istemcileri ve ağ cihazlarını merkezi olarak yönetmek için açık kaynaklı bir platform. HAVELSAN tarafından geliştirilen bu sistem, genişletilebilir eklenti mimarisi ile güvenli sunucu yönetimi sağlar.

## Teknoloji Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.4)
- **Veritabanı**: PostgreSQL
- **Auth**: JWT (php-open-source-saver/jwt-auth)
- **Cache**: Redis
- **Queue**: Laravel Queue
- **WebSocket**: Laravel Reverb

### Frontend
- External bir NextJS uygulaması kullanılıyor. Bu sistem sadece API olarak hizmet vermekte.

### Deployment
- **Container**: Docker (Ubuntu Jammy)
- **Web Server**: Nginx
- **PHP**: PHP-FPM 8.4
- **Process Manager**: Supervisor

## Proje Yapısı

```
/liman/server/
├── app/
│   ├── Classes/              # Özel sınıflar
│   │   ├── Authentication/   # Auth adaptörleri (Liman, Keycloak, LDAP, OIDC)
│   │   ├── Ldap.php         # LDAP bağlantı sınıfı
│   │   └── NotificationBuilder.php
│   ├── Connectors/          # Sunucu bağlantı adaptörleri
│   │   ├── Connector.php
│   │   └── GenericConnector.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/         # REST API endpoints
│   │   │   ├── Extension/   # Eklenti sandbox
│   │   │   └── HASync/      # Yüksek erişilebilirlik sistemi yardımcı endpointleri
│   │   ├── Middleware/      # Auth, permission, server middlewares
│   │   └── Helpers.php      # Global helper fonksiyonlar
│   ├── Models/              # Eloquent modeller
│   ├── System/              # Sistem seviye sınıflar
│   └── ...
├── config/                  # Laravel config dosyaları
├── database/
│   ├── migrations/          # Veritabanı migrasyonları
│   └── seeds/               # Seed dosyaları
├── routes/
│   ├── api.php             # API rotaları
│   ├── web.php             # Web rotaları
│   └── ...
├── storage/                 # Dosya depolama
└── resources/
    ├── views/              # Blade templates
    └── assets/             # Frontend assets
```

## Önemli Özellikler

### 1. Multi-Authentication System
- **Liman Auth**: Yerli kullanıcı sistemi
- **Keycloak**: OAuth2/OIDC entegrasyonu
- **LDAP**: Active Directory/OpenLDAP desteği
- **OIDC**: Generic OIDC provider desteği

### 2. Sunucu Yönetimi
- SSH/WinRM üzerinden uzak komut çalıştırma
- Dosya transferi ve yönetimi
- Gerçek zamanlı sistem monitörü
- Kubernetes cluster yönetimi

### 3. Eklenti Sistemi
- Sandbox ortamında eklenti çalıştırma
- PHP/JavaScript eklenti desteği
- Rol tabanlı eklenti yetkilendirme

### 4. Güvenlik
- JWT token authentication
- Role-based access control (RBAC)
- Multi-factor authentication (2FA)
- SSL/TLS certificate management
- IP range restrictions

### 5. Monitoring & Logging
- Audit logging
- Authentication logs
- Performance monitoring
- Real-time notifications

## Veri Modelleri

### Ana Modeller
- **User**: Kullanıcı yönetimi (local, ldap, keycloak, oidc auth types)
- **Server**: Sunucu bilgileri ve bağlantı detayları
- **Extension**: Eklenti meta verileri ve lisans bilgileri
- **Role**: Rol tanımları ve yetkilendirme
- **Permission**: Granular izin sistemi
- **Certificate**: SSL sertifika yönetimi
- **Notification**: Bildirim modeli

### Auth Modelleri
- **AccessToken**: API token yönetimi
- **AuthLog**: Giriş logları
- **Oauth2Token**: OAuth2 token storage
- **AuditLog**: Sistem denetim logları

## API Endpoints

### Authentication (`/api/auth/`)
- `POST /login`: Kullanıcı girişi (multi-auth support)
- `POST /logout`: Çıkış
- `POST /refresh`: Token yenileme
- `GET /user`: Kullanıcı profili
- `POST /change_password`: Şifre değiştirme
- `POST /setup_mfa`: 2FA kurulumu

### Server Management (`/api/servers/`)
- `GET /`: Sunucu listesi
- `POST /`: Yeni sunucu ekleme
- `GET /{id}`: Sunucu detayları
- `GET /{id}/specs`: Sunucu özellikleri
- `POST /{id}/users/local`: Yerel kullanıcı ekleme

### Extension Management (`/api/extensions/`)
- `GET /`: Eklenti listesi
- `POST /assign`: Sunucuya eklenti atama
- `POST /unassign`: Eklenti atama kaldırma

### Settings (`/api/settings/`)
- `GET|POST /users/`: Kullanıcı yönetimi
- `GET|POST /roles/`: Rol yönetimi
- `GET|POST /extensions/`: Eklenti yönetimi
- `GET|POST /certificates/`: Sertifika yönetimi

## Önemli Sınıflar ve Fonksiyonlar

### Helper Functions (`app/Http/Helpers.php`)
```php
validate($rules, $messages = [], $fieldNames = []): void
user(): User                    // Authenticated user
server(): Server               // Current server context
extension(): Extension         // Current extension context
sudo(): string                 // Sudo command prefix
```

### Authentication Classes
- `Authenticator`: Token management
- `KeycloakAuthenticator`: Keycloak entegrasyonu
- `LDAPAuthenticator`: LDAP entegrasyonu
- `LimanAuthenticator`: Yerli auth sistem
- `OIDCAuthenticator`: Generic OIDC provider integration

### Server Connection
- `GenericConnector`: SSH/WinRM bağlantı yönetimi
- `Command`: Güvenli komut çalıştırma wrapper

## Güvenlik Considerations

### 1. Command Injection Prevention
- Tüm shell komutları `Command::clean()` ile sanitize edilir
- Parameterized queries kullanılır: `@{:param}` (quoted), `{:param}` (raw)

### 2. Permission System
- Granular permission kontrolü: `Permission::can($userId, $object, $field, $value)`
- Middleware: `permissions`, `admin`, `server`

### 3. Input Validation
- `validate()` helper ile request validation
- Laravel validation rules

### 4. Audit Logging
- Tüm kritik işlemler `AuditLog` ile kaydedilir
- User actions tracking

## Development Workflow

### Environment Setup
1. PHP 8.4+ kurulumu
2. Composer dependencies: `composer install`
3. Node.js + pnpm: `pnpm install`
4. Environment: `.env` dosyası konfigürasyonu
5. Database migration: `php artisan migrate`

### Build Process
- Frontend: `pnpm run dev` (development), `pnpm run prod` (production)
- Backend: `composer dump-autoload`

### Testing
- No testing system included in this project

## Extension Development

### Extension Sandbox
- Eklentiler sandbox ortamında çalışır
- `app/Http/Controllers/Extension/Sandbox/` altında internal API
- Güvenlik kısıtlamaları: file system access, network restrictions

## Deployment

### Docker
```dockerfile
FROM ubuntu:jammy
# PHP 8.4, Nginx, Supervisor
# Laravel optimizasyonları
# Extension environment
```

### Production Considerations
- `APP_ENV=production`
- Redis cache optimization
- Nginx reverse proxy
- SSL certificate management
- Log rotation

## Error Handling

### Custom Exceptions
- `JsonResponseException`: API error responses
- `ValidationException`: Input validation errors

### Logging
- Laravel Log facade
- Different log levels: emergency, alert, critical, error, warning, notice, info, debug
- Structured logging for audit trails

## Performance Optimization

### Database
- Query optimization with indexes
- Eloquent relationship eager loading
- Database connection pooling

### Caching
- Redis cache for frequently accessed data
- Query result caching
- Session storage in Redis

### Frontend
- Asset minification and compression
- Lazy loading for large datasets
- WebSocket for real-time updates

## Monitoring and Maintenance

### Health Checks
- `GET /api/settings/health/`: System health status
- Database connectivity
- Redis connectivity  
- Disk space monitoring

### Backup Strategy
- Database backups
- Extension data backups
- Configuration backups

## Common Development Patterns

### Controller Pattern
```php
class ExampleController extends Controller
{
    public function index()
    {
        // Permission check
        if (!Permission::can(user()->id, 'resource', 'action')) {
            throw new JsonResponseException(['message' => 'Unauthorized'], '', 403);
        }
        
        // Business logic
        return response()->json($data);
    }
}
```

### Model Relationships
```php
class Server extends Model
{
    use UsesUuid;
    
    public function extensions()
    {
        return $this->belongsToMany(Extension::class, 'server_extensions');
    }
}
```

### Middleware Usage
```php
Route::group(['middleware' => ['auth:api', 'permissions']], function () {
    // Protected routes
});
```

## Troubleshooting

### Common Issues
1. **Permission Denied**: Check user roles and permissions
2. **Connection Failed**: Verify server credentials and network
3. **Extension Errors**: Check extension logs and sandbox restrictions
4. **Auth Issues**: Verify JWT configuration and token expiry

### Debug Mode
- Set `APP_DEBUG=true` for detailed error messages
- Use `Log::debug()` for debugging information

## Best Practices

1. **Security First**: Always validate input, sanitize output
2. **Permission Checks**: Implement at controller level
3. **Error Handling**: Use proper HTTP status codes
4. **Logging**: Log all important actions
5. **Performance**: Use caching and query optimization
6. **Documentation**: Comment complex business logic
7. **Testing**: Write tests for critical functionality

Bu instructions dosyası, Liman MYS projesinin tüm önemli yönlerini kapsar ve geliştiricilere rehberlik eder.
