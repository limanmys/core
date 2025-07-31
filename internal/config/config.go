package config

import (
	"fmt"
	"log"
	"os"
	"strconv"
	"strings"

	"github.com/joho/godotenv"
)

type Config struct {
	App       AppConfig       `json:"app"`
	Database  DatabaseConfig  `json:"database"`
	Auth      AuthConfig      `json:"auth"`
	Mail      MailConfig      `json:"mail"`
	Market    MarketConfig    `json:"market"`
	Redis     RedisConfig     `json:"redis"`
	Websocket WebsocketConfig `json:"websocket"`
}

type AppConfig struct {
	Name                   string `json:"name"`
	Env                    string `json:"env"`
	Key                    string `json:"key"`
	Debug                  bool   `json:"debug"`
	URL                    string `json:"url"`
	NotificationEmail      string `json:"notification_email"`
	Lang                   string `json:"lang"`
	Version                string `json:"version"`
	BrandName              string `json:"brand_name"`
	DefaultAuthGate        string `json:"default_auth_gate"`
	ExtensionTimeout       int    `json:"extension_timeout"`
	Branding               string `json:"branding"`
	RenderEngineAddress    string `json:"render_engine_address"`
	HighAvailabilityMode   bool   `json:"high_availability_mode"`
	LDAPIgnoreCert         bool   `json:"ldap_ignore_cert"`
	ExtensionDeveloperMode bool   `json:"extension_developer_mode"`
}

type DatabaseConfig struct {
	Connection string `json:"connection"`
	Host       string `json:"host"`
	Port       int    `json:"port"`
	Database   string `json:"database"`
	Username   string `json:"username"`
	Password   string `json:"password"`
	Debug      bool   `json:"debug"`
}

type AuthConfig struct {
	SessionExpiresOnClose bool           `json:"session_expires_on_close"`
	OTPEnabled            bool           `json:"otp_enabled"`
	Keycloak              KeycloakConfig `json:"keycloak"`
	OIDC                  OIDCConfig     `json:"oidc"`
}

type KeycloakConfig struct {
	Active       bool   `json:"active"`
	ClientID     string `json:"client_id"`
	ClientSecret string `json:"client_secret"`
	RedirectURI  string `json:"redirect_uri"`
	BaseURL      string `json:"base_url"`
	Realm        string `json:"realm"`
}

type OIDCConfig struct {
	Active           bool   `json:"active"`
	IssuerURL        string `json:"issuer_url"`
	ClientID         string `json:"client_id"`
	ClientSecret     string `json:"client_secret"`
	RedirectURI      string `json:"redirect_uri"`
	AuthEndpoint     string `json:"auth_endpoint"`
	UserinfoEndpoint string `json:"userinfo_endpoint"`
	TokenEndpoint    string `json:"token_endpoint"`
}

type MailConfig struct {
	Enabled    bool   `json:"enabled"`
	Mailer     string `json:"mailer"`
	Host       string `json:"host"`
	Port       int    `json:"port"`
	Username   string `json:"username"`
	Password   string `json:"password"`
	Encryption string `json:"encryption"`
}

type MarketConfig struct {
	URL          string `json:"url"`
	ClientID     string `json:"client_id"`
	ClientSecret string `json:"client_secret"`
}

type RedisConfig struct {
	Host     string `json:"host"`
	Port     int    `json:"port"`
	Password string `json:"password"`
	Database int    `json:"database"`
}

type WebsocketConfig struct {
	AppID     string `json:"app_id"`
	AppKey    string `json:"app_key"`
	AppSecret string `json:"app_secret"`
	Host      string `json:"host"`
	Port      int    `json:"port"`
	Scheme    string `json:"scheme"`
}

// Load reads configuration from .env file
func Load() (*Config, error) {
	// Load .env file
	if err := godotenv.Load(); err != nil {
		log.Printf("Warning: .env file not found, using environment variables: %v", err)
	}

	config := &Config{
		App: AppConfig{
			Name:                   getEnv("APP_NAME", "Liman"),
			Env:                    getEnv("APP_ENV", "production"),
			Key:                    getEnv("APP_KEY", ""),
			Debug:                  getEnvAsBool("APP_DEBUG", false),
			URL:                    getEnv("APP_URL", "https://liman.dev"),
			NotificationEmail:      getEnv("APP_NOTIFICATION_EMAIL", "destek@liman.dev"),
			Lang:                   getEnv("APP_LANG", "en"),
			Version:                getEnv("APP_VERSION", "1.0.0"),
			BrandName:              getEnv("BRAND_NAME", "HAVELSAN © 2023"),
			DefaultAuthGate:        getEnv("DEFAULT_AUTH_GATE", "liman"),
			ExtensionTimeout:       getEnvAsInt("EXTENSION_TIMEOUT", 30),
			Branding:               getEnv("BRANDING", ""),
			RenderEngineAddress:    getEnv("RENDER_ENGINE_ADDRESS", "https://127.0.0.1:2806"),
			HighAvailabilityMode:   getEnvAsBool("HIGH_AVAILABILITY_MODE", false),
			LDAPIgnoreCert:         getEnvAsBool("LDAP_IGNORE_CERT", false),
			ExtensionDeveloperMode: getEnvAsBool("EXTENSION_DEVELOPER_MODE", false),
		},
		Database: DatabaseConfig{
			Connection: getEnv("DB_CONNECTION", "pgsql"),
			Host:       getEnv("DB_HOST", "127.0.0.1"),
			Port:       getEnvAsInt("DB_PORT", 5432),
			Database:   getEnv("DB_DATABASE", "liman"),
			Username:   getEnv("DB_USERNAME", "liman"),
			Password:   getEnv("DB_PASSWORD", ""),
			Debug:      getEnvAsBool("DB_DEBUG", false),
		},
		Auth: AuthConfig{
			SessionExpiresOnClose: getEnvAsBool("AUTH_SESSION_EXPIRES_ON_CLOSE", false),
			OTPEnabled:            getEnvAsBool("OTP_ENABLED", false),
			Keycloak: KeycloakConfig{
				Active:       getEnvAsBool("KEYCLOAK_ACTIVE", false),
				ClientID:     getEnv("KEYCLOAK_CLIENT_ID", ""),
				ClientSecret: getEnv("KEYCLOAK_CLIENT_SECRET", ""),
				RedirectURI:  getEnv("KEYCLOAK_REDIRECT_URI", ""),
				BaseURL:      getEnv("KEYCLOAK_BASE_URL", ""),
				Realm:        getEnv("KEYCLOAK_REALM", ""),
			},
			OIDC: OIDCConfig{
				Active:           getEnvAsBool("OIDC_ACTIVE", false),
				IssuerURL:        getEnv("OIDC_ISSUER_URL", ""),
				ClientID:         getEnv("OIDC_CLIENT_ID", ""),
				ClientSecret:     getEnv("OIDC_CLIENT_SECRET", ""),
				RedirectURI:      getEnv("OIDC_REDIRECT_URI", ""),
				AuthEndpoint:     getEnv("OIDC_AUTH_ENDPOINT", "/authorize"),
				UserinfoEndpoint: getEnv("OIDC_USERINFO_ENDPOINT", "/userinfo"),
				TokenEndpoint:    getEnv("OIDC_TOKEN_ENDPOINT", "/oauth/token"),
			},
		},
		Mail: MailConfig{
			Enabled:    getEnvAsBool("MAIL_ENABLED", false),
			Mailer:     getEnv("MAIL_MAILER", "smtp"),
			Host:       getEnv("MAIL_HOST", "0.0.0.0"),
			Port:       getEnvAsInt("MAIL_PORT", 1025),
			Username:   getEnv("MAIL_USERNAME", ""),
			Password:   getEnv("MAIL_PASSWORD", ""),
			Encryption: getEnv("MAIL_ENCRYPTION", "tls"),
		},
		Market: MarketConfig{
			URL:          getEnv("MARKET_URL", "https://market.liman.dev"),
			ClientID:     getEnv("MARKET_CLIENT_ID", ""),
			ClientSecret: getEnv("MARKET_CLIENT_SECRET", ""),
		},
		Redis: RedisConfig{
			Host:     getEnv("REDIS_HOST", "127.0.0.1"),
			Port:     getEnvAsInt("REDIS_PORT", 6379),
			Password: getEnv("REDIS_PASSWORD", ""),
			Database: getEnvAsInt("REDIS_DATABASE", 0),
		},
		Websocket: WebsocketConfig{
			AppID:     getEnv("WEBSOCKET_APP_ID", "app"),
			AppKey:    getEnv("WEBSOCKET_APP_KEY", "liman-key"),
			AppSecret: getEnv("WEBSOCKET_APP_SECRET", "liman-secret"),
			Host:      getEnv("WEBSOCKET_HOST", "127.0.0.1"),
			Port:      getEnvAsInt("WEBSOCKET_PORT", 6001),
			Scheme:    getEnv("WEBSOCKET_SCHEME", "http"),
		},
	}

	return config, nil
}

// Helper functions
func getEnv(key, fallback string) string {
	if value, exists := os.LookupEnv(key); exists {
		return value
	}
	return fallback
}

func getEnvAsInt(name string, fallback int) int {
	valueStr := getEnv(name, "")
	if value, err := strconv.Atoi(valueStr); err == nil {
		return value
	}
	return fallback
}

func getEnvAsBool(name string, fallback bool) bool {
	valueStr := getEnv(name, "")
	if value, err := strconv.ParseBool(valueStr); err == nil {
		return value
	}
	// Handle string values like "true", "false", "1", "0"
	switch strings.ToLower(valueStr) {
	case "true", "1", "yes", "on":
		return true
	case "false", "0", "no", "off":
		return false
	}
	return fallback
}

// GetDSN returns database connection string
func (c *DatabaseConfig) GetDSN() string {
	return fmt.Sprintf("host=%s port=%d user=%s password=%s dbname=%s sslmode=disable",
		c.Host, c.Port, c.Username, c.Password, c.Database)
}

// GetRedisAddr returns Redis connection address
func (c *RedisConfig) GetRedisAddr() string {
	return fmt.Sprintf("%s:%d", c.Host, c.Port)
}
