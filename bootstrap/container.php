<?php
/**
 * Bootstrap — DI Container Wiring
 * 
 * Registers all service and repository bindings in the DI Container.
 * Loaded once during application bootstrap before any controller runs.
 * 
 * Usage in controllers:
 *   $saleService = Container::make('SaleService');
 *   $processor   = Container::make('LineItemProcessor');
 */

// ── Database ──
Container::singleton('Database', function () {
    return Database::getInstance();
});

// ── Request ──
Container::singleton('Request', function () {
    return Request::capture();
});

// ── Repositories ──
Container::singleton('SaleRepository', function () {
    return new SaleRepository(Container::make('Database'));
});

Container::singleton('CustomerRepository', function () {
    return new CustomerRepository(Container::make('Database'));
});

Container::singleton('ProductRepository', function () {
    return new ProductRepository(Container::make('Database'));
});

// ── Services ──
Container::singleton('LineItemProcessor', function () {
    return new LineItemProcessor();
});

Container::singleton('StockService', function () {
    return new StockService(Container::make('ProductRepository'));
});

Container::singleton('SaleService', function () {
    return new SaleService(
        Container::make('Database'),
        Container::make('SaleRepository'),
        Container::make('CustomerRepository'),
        Container::make('StockService')
    );
});

// ── Router ──
Container::singleton('Router', function () {
    return new Router();
});
