package di

import (
	"go.uber.org/fx"
	"gorm.io/gorm"

	"github.com/limanmys/core/internal/repositories"
)

// RepositoryModule provides repository dependencies
var RepositoryModule = fx.Module("repositories",
	fx.Provide(NewUserRepository),
)

// NewUserRepository creates a new user repository
func NewUserRepository(db *gorm.DB) repositories.UserRepository {
	return repositories.NewUserRepository(db)
}
