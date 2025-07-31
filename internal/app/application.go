package app

import (
	"context"

	"github.com/gofiber/fiber/v3"
	"go.uber.org/zap"

	"github.com/limanmys/core/app/controllers/users"
	"github.com/limanmys/core/app/middleware"
	"github.com/limanmys/core/app/routes"
	"github.com/limanmys/core/internal/config"
	"github.com/limanmys/core/internal/jwtware"
	"github.com/limanmys/core/internal/migration"
	"github.com/limanmys/core/internal/services"
	"github.com/limanmys/core/pkg/seeds"
	"github.com/limanmys/core/utils"
)

// Application represents the main application
type Application struct {
	App            *fiber.App
	router         *routes.Router
	userController *users.Controller
	userService    services.UserService
	migrationSvc   *migration.Service
	config         *config.Config
	logger         *zap.Logger
}

// NewApplication creates a new application instance
func NewApplication(
	app *fiber.App,
	router *routes.Router,
	userController *users.Controller,
	userService services.UserService,
	migrationSvc *migration.Service,
	config *config.Config,
	logger *zap.Logger,
) *Application {
	return &Application{
		App:            app,
		router:         router,
		userController: userController,
		userService:    userService,
		migrationSvc:   migrationSvc,
		config:         config,
		logger:         logger,
	}
}

// Start starts the application
func (a *Application) Start(ctx context.Context) error {
	a.logger.Info("Starting application...")

	// Run migrations
	if err := a.migrationSvc.AutoMigrate(); err != nil {
		a.logger.Error("Migration failed", zap.Error(err))
		return err
	}

	// Run seeds
	if err := seeds.Init(a.migrationSvc.GetDB()); err != nil {
		a.logger.Error("Seeding failed", zap.Error(err))
		return err
	}

	a.App.Use(middleware.TokenLookup)
	a.App.Use(a.createProtectedMiddleware())

	// Setup login route (public)
	a.App.Post("/api/v1/login", a.userController.Login)

	// Setup all other routes
	a.router.Setup()

	a.logger.Info("Application started successfully", zap.String("env", a.config.App.Env))
	return nil
}

// Stop stops the application
func (a *Application) Stop(ctx context.Context) error {
	a.logger.Info("Stopping application...")
	return a.App.Shutdown()
}

// createProtectedMiddleware creates the JWT protection middleware
func (a *Application) createProtectedMiddleware() fiber.Handler {
	return jwtware.New(jwtware.Config{
		SigningKey: jwtware.SigningKey{
			JWTAlg: "HS256",
			Key:    []byte(a.config.App.Key),
		},
		ErrorHandler: func(c fiber.Ctx, err error) error {
			return utils.NewAuthError()
		},
		ContextKey: "token",
		SuccessHandler: func(c fiber.Ctx) error {
			claims := utils.GetClaimFromContext(c)
			user, err := a.userService.GetByID(claims.ID)
			if err != nil {
				return utils.NewAuthError()
			}
			user.Password = ""
			c.Locals("user", user)
			return c.Next()
		},
	})
}
