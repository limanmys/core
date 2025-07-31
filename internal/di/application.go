package di

import (
	"github.com/gofiber/fiber/v3"
	"go.uber.org/fx"
	"go.uber.org/zap"

	"github.com/limanmys/core/app/controllers/users"
	"github.com/limanmys/core/app/routes"
	"github.com/limanmys/core/internal/app"
	"github.com/limanmys/core/internal/config"
	"github.com/limanmys/core/internal/migration"
	"github.com/limanmys/core/internal/services"
)

// ApplicationModule provides application dependency
var ApplicationModule = fx.Module("application",
	fx.Provide(NewApplication),
)

// NewApplication creates a new application instance
func NewApplication(
	fiberApp *fiber.App,
	router *routes.Router,
	userController *users.Controller,
	userService services.UserService,
	migrationSvc *migration.Service,
	config *config.Config,
	logger *zap.Logger,
) *app.Application {
	return app.NewApplication(
		fiberApp,
		router,
		userController,
		userService,
		migrationSvc,
		config,
		logger,
	)
}
