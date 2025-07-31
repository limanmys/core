# Liman MYS (Merkezi Yönetim Sistemi) - GitHub Copilot Instructions

## Proje Genel Bakış

Liman, sunucuları, istemcileri ve ağ cihazlarını merkezi olarak yönetmek için açık kaynaklı bir platform. HAVELSAN tarafından geliştirilen bu sistem, genişletilebilir eklenti mimarisi ile güvenli sunucu yönetimi sağlar. Bu proje, PHP Laravel'den Golang'e migrate edilmiştir.

## Teknoloji Stack

### Backend
- **Framework**: Fiber v3 (Golang 1.24+)
- **ORM**: GORM v2
- **Veritabanı**: PostgreSQL 15+
- **Auth**: JWT-go (golang-jwt/jwt/v5)
- **Cache**: Redis 7+
- **Queue**: Asynq (Hibiken)
- **WebSocket**: Fiber Websocket
- **Validation**: Go Validator (go-playground/validator/v10)
- **Migration**: Custom GORM-based migration system

### Frontend
- External bir NextJS uygulaması kullanılıyor. Bu sistem sadece API olarak hizmet vermekte.

### Deployment
- **Container**: Docker (Alpine Linux)
- **Web Server**: Nginx (reverse proxy)
- **Binary**: Single binary executable
- **Process Manager**: Systemd/Docker Compose

## Önemli Özellikler

### 1. Multi-Authentication System
- **Liman Auth**: Yerel kullanıcı sistemi
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

### Helper Functions (`internal/utils/helpers.go`)
```go
func ValidateRequest(ctx *fiber.Ctx, rules map[string]string) error
func GetAuthenticatedUser(ctx *fiber.Ctx) (*models.User, error)
func GetCurrentServer(ctx *fiber.Ctx) (*models.Server, error)
func GetCurrentExtension(ctx *fiber.Ctx) (*models.Extension, error)
func GetSudoCommand() string
```

### Authentication Services
- `Authenticator`: Token management
- `KeycloakAuthenticator`: Keycloak entegrasyonu
- `LDAPAuthenticator`: LDAP entegrasyonu
- `LimanAuthenticator`: Yerli auth sistem
- `OIDCAuthenticator`: Generic OIDC provider integration

### Server Connection
- `GenericConnector`: SSH/WinRM bağlantı yönetimi
- `CommandRunner`: Güvenli komut çalıştırma wrapper

## Güvenlik Considerations

### 1. Command Injection Prevention
- Tüm shell komutları `CommandRunner.Sanitize()` ile sanitize edilir
- Parameterized queries kullanılır: `@{:param}` (quoted), `{:param}` (raw)

### 2. Permission System
- Granular permission kontrolü: `permission.Can(userID, object, field, value)`
- Middleware: `auth.Required`, `permission.Check`, `server.Context`

### 3. Input Validation
- `ValidateRequest()` helper ile request validation
- Struct tag validation ile giriş kontrolü

### 4. Audit Logging
- Tüm kritik işlemler `AuditLog` modeli ile kaydedilir
- User actions tracking

## Development Workflow

### Environment Setup
1. Go 1.24+ kurulumu
2. Dependencies: `go mod tidy`
3. Environment: `.env` dosyası konfigürasyonu
4. Database migration: `go run cmd/migrate/main.go`
5. Redis connection setup

### Build Process
- Development: `go run cmd/server/main.go`
- Production: `go build -o bin/liman-server cmd/server/main.go`
- Cross-compilation: `GOOS=linux GOARCH=amd64 go build`

### Migration System
- Migration files: `internal/migrations/`
- Run migrations: `go run cmd/migrate/main.go`
- Create migration: `go run cmd/tools/main.go create-migration <name>`

### Testing
- Unit tests: `go test ./...`
- Integration tests: `go test -tags=integration ./...`

## Extension Development

### Extension Sandbox
- Eklentiler sandbox ortamında çalışır
- `internal/handlers/extension/sandbox/` altında internal API
- Güvenlik kısıtlamaları: file system access, network restrictions

## Deployment

### Docker
```dockerfile
FROM alpine:latest
# Go binary
# Nginx reverse proxy
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
- GORM relationship preloading
- Connection pooling

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

### Handler Pattern
```go
func (h *ExampleHandler) Index(ctx *fiber.Ctx) error {
    // Permission check
    user, err := utils.GetAuthenticatedUser(ctx)
    if err != nil {
        return fiber.NewError(fiber.StatusUnauthorized, "Unauthorized")
    }
    
    if !permission.Can(user.ID, "resource", "action") {
        return fiber.NewError(fiber.StatusForbidden, "Forbidden")
    }
    
    // Business logic
    return ctx.JSON(data)
}
```

### Model Relationships
```go
type Server struct {
    gorm.Model
    ID         uuid.UUID   `gorm:"type:uuid;primary_key"`
    Name       string      `gorm:"not null"`
    Extensions []Extension `gorm:"many2many:server_extensions;"`
}
```

### Middleware Usage
```go
api := app.Group("/api")
api.Use(middleware.Auth())
api.Use(middleware.Permission())
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
