package di

import (
	"github.com/gofiber/fiber/v3"
	"go.uber.org/fx"
	"go.uber.org/zap"

	"github.com/limanmys/core/app/controllers/users"
	"github.com/limanmys/core/app/routes"
)

// RouterModule provides router dependency
var RouterModule = fx.Module("router",
	fx.Provide(NewRouter),
)

// NewRouter creates a new router instance
func NewRouter(app *fiber.App, userController *users.Controller, logger *zap.Logger) *routes.Router {
	return routes.NewRouter(app, userController, logger)
}
