package routes

import (
	"github.com/gofiber/fiber/v3"
	"go.uber.org/zap"

	"github.com/limanmys/core/app/controllers/users"
	"github.com/limanmys/core/app/middleware"
)

// Router handles route registration
type Router struct {
	app            *fiber.App
	userController *users.Controller
	logger         *zap.Logger
}

// NewRouter creates a new router instance
func NewRouter(app *fiber.App, userController *users.Controller, logger *zap.Logger) *Router {
	return &Router{
		app:            app,
		userController: userController,
		logger:         logger,
	}
}

// Setup configures all application routes
func (r *Router) Setup() {
	r.logger.Info("Setting up routes...")

	// API v1 group
	apiV1 := r.app.Group("/api/v1")

	// User routes
	r.setupUserRoutes(apiV1)

	r.logger.Info("Routes setup completed")
}

// setupUserRoutes configures user-related routes
func (r *Router) setupUserRoutes(api fiber.Router) {
	userGroup := api.Group("/users")

	// Public routes (require authentication but not admin)
	userGroup.Get("/me", r.userController.CurrentUser)

	// Admin only routes
	userGroup.Use(middleware.IsAdmin)
	userGroup.Get("/", r.userController.Index)
	userGroup.Post("/", r.userController.Create)
	userGroup.Get("/:id", r.userController.Show)
	userGroup.Patch("/:id", r.userController.Update)
	userGroup.Delete("/:id", r.userController.Delete)
}
