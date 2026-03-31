# Balero Framework/CMS

A zero-dependency, annotation-driven PHP framework inspired by Spring Boot.

---

## Overview

Balero is a lightweight PHP framework built for developers who prefer structured, predictable backend architectures.

It brings an enterprise-style development experience to PHP, combining:

* Attribute-based routing
* Dependency Injection (DI)
* DTO-driven development
* JSON APIs and server-side templates
* Built-in testing with mock injection

This repository also includes a minimal CMS built on top of the framework as a real-world implementation.

---

## 🏢 Enterprise-style Architecture

Balero is designed with patterns commonly found in enterprise frameworks like Spring Boot:

* Clear separation of layers (Controller / Service / DTO)
* Dependency Injection via attributes (`#[Inject]`)
* Annotation-driven configuration
* Predictable request lifecycle
* Testable components with mock injection

This allows developers to build scalable and maintainable applications without relying on heavy frameworks.

---

## Why Balero?

After working with Spring Boot, many PHP frameworks feel either too unstructured or overly magical.

Balero provides a more disciplined approach:

* Explicit behavior over hidden magic
* Minimal boilerplate with structured patterns
* Familiar architecture for enterprise developers

---

## Key Features

### 🧠 Dependency Injection

Native DI using attributes:

```php
#[Inject]
private AdminService $adminService;

#[Inject]
private View $view;
```

No container configuration required.

---

### 🧩 DTO-driven development (low boilerplate)

```php
$settingsDTO = new SettingsDTO();
$settingsDTO->fromRequest($this->request);
```

Encapsulate request data cleanly without repetitive mapping logic.

Inspired by patterns used alongside Project Lombok.

---

### ⚡ Annotation-driven Controllers

```php
#[Controller('/admin')]
#[Auth(required: true)]
class AdminController {

    #[Get('/settings')]
    public function getSettings() {
        return $this->view->render("admin/dashboard.html", []);
    }

    #[Post('/settings')]
    public function postSettings() {
        return ['status' => 'ok'];
    }
}
```

Structured, readable, and predictable.

---

### 🔌 Hybrid Responses (HTML + JSON)

```php
#[Post('/uploader')]
#[JsonResponse]
public function postUploader() {
    return ['status' => 'ok'];
}
```

* Use templates for views
* Use JSON for APIs

---

### 🧪 Testing with Mock Injection

Built-in testing utilities inspired by Mockito.

```php
#[InjectMocks]
private AdminService $adminService;
```

* Easy unit testing
* Clean dependency mocking
* No external libraries

---

### 🔐 Built-in Features via Attributes

```php
#[Auth(required: true)]
#[FlashStorage]
```

* Authentication handling
* Flash messaging
* Request abstraction

---

### 🧼 Zero Dependencies

* No Composer dependencies
* No external frameworks
* Full control over the stack

---

## What is included?

* Core framework (routing, DI, controllers)
* Template engine
* JSON response handling
* DTO utilities
* Testing utilities (mock injection)
* Lightweight CMS (reference implementation)

---

## Use Cases

* REST APIs
* Lightweight CMS
* Internal tools
* Prototypes
* Structured backend applications

---

## Docker

Balero is Docker-ready out of the box.

---

## Philosophy

* No magic
* No dependencies
* Full control
* Structured development

---

## Included CMS

This repository includes a minimal CMS built on top of Balero.

It serves as:

* a real-world example
* a starter project
* a reference implementation of the framework

---

## Positioning

Balero is designed for developers who:

* come from Java / enterprise environments
* are familiar with Spring Boot
* want structure instead of “magic”
* prefer predictable backend architecture

---

## Documentation

https://librixsoft.github.io/balerocms-docs/latest/
