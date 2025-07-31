package di

import (
	"go.uber.org/fx"

	"github.com/limanmys/core/internal/repositories"
	"github.com/limanmys/core/internal/services"
)

// ServiceModule provides service dependencies
var ServiceModule = fx.Module("services",
	fx.Provide(NewUserService),
)

// NewUserService creates a new user service
func NewUserService(userRepo repositories.UserRepository) services.UserService {
	return services.NewUserService(userRepo)
}
