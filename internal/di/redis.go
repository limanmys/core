package di

import (
	"context"
	"fmt"

	"github.com/redis/go-redis/v9"
	"go.uber.org/fx"
	"go.uber.org/zap"

	"github.com/limanmys/core/internal/config"
)

// RedisModule provides Redis dependency
var RedisModule = fx.Module("redis",
	fx.Provide(NewRedisClient),
)

// NewRedisClient creates a new Redis client
func NewRedisClient(conf *config.Config, logger *zap.Logger) (*redis.Client, error) {
	logger.Info("Connecting to Redis...")

	rdb := redis.NewClient(&redis.Options{
		Addr:     fmt.Sprintf("%s:%d", conf.Redis.Host, conf.Redis.Port),
		Password: conf.Redis.Password,
		DB:       conf.Redis.Database,
	})

	// Test connection
	ctx := context.Background()
	_, err := rdb.Ping(ctx).Result()
	if err != nil {
		logger.Error("Failed to connect to Redis", zap.Error(err))
		return nil, err
	}

	logger.Info("Redis connection established successfully")
	return rdb, nil
}
