package di

import (
	"encoding/json"

	"github.com/gofiber/fiber/v3"
	"github.com/gofiber/fiber/v3/middleware/compress"
	"github.com/gofiber/fiber/v3/middleware/encryptcookie"
	"github.com/gofiber/fiber/v3/middleware/logger"
	"github.com/gofiber/fiber/v3/middleware/recover"
	"go.uber.org/fx"
	"go.uber.org/zap"

	"github.com/limanmys/core/internal/config"
	"github.com/limanmys/core/internal/error_handler"
)

// ServerModule provides Fiber server dependency
var ServerModule = fx.Module("server",
	fx.Provide(NewFiberApp),
)

// NewFiberApp creates a new Fiber application
func NewFiberApp(conf *config.Config, zapLogger *zap.Logger) *fiber.App {
	zapLogger.Info("Creating Fiber application...")

	adminConfig := fiber.Config{
		BodyLimit:    32 * 1024 * 1024,
		JSONEncoder:  json.Marshal,
		JSONDecoder:  json.Unmarshal,
		ErrorHandler: error_handler.ErrorHandler,
	}

	app := fiber.New(adminConfig)

	// Middleware setup
	app.Use(recover.New(recover.Config{EnableStackTrace: true}))
	app.Use(compress.New())
	app.Use(logger.New())

	// Cookie encryption middleware
	app.Use(encryptcookie.New(encryptcookie.Config{
		Key: conf.App.Key,
	}))

	zapLogger.Info("Fiber application created successfully")
	return app
}
