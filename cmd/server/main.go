package main

import (
	"context"
	"log"

	"go.uber.org/fx"

	"github.com/limanmys/core/internal/app"
	"github.com/limanmys/core/internal/di"
)

func main() {
	fx.New(
		di.Container,
		fx.Invoke(func(app *app.Application) {
			ctx := context.Background()
			if err := app.Start(ctx); err != nil {
				log.Fatalf("Failed to start application: %v", err)
			}

			log.Fatal(app.App.Listen("0.0.0.0:2878"))
		}),
	).Run()
}
