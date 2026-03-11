<?php
/**
 * Service Container Bindings
 * 
 * Registers all application services, repositories, and core dependencies
 * into the dependency injection container.
 */

// Core Services
Container::singleton('db', fn() => Database::getInstance());

// Note: Repositories and Domain Services will be registered here later in this phase.
