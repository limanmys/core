package di

import (
	"go.uber.org/fx"
	"go.uber.org/zap"

	"github.com/limanmys/core/app/controllers/users"
	"github.com/limanmys/core/internal/services"
)

// UserControllerModule provides user controller dependency
var UserControllerModule = fx.Module("user_controller",
	fx.Provide(NewUserController),
)

// NewUserController creates a new user controller
func NewUserController(userService services.UserService, logger *zap.Logger) *users.Controller {
	return users.NewController(userService, logger)
}
