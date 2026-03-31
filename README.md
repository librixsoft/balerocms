# Balero Framework/CMS

A modern, lightweight, enterprise-style PHP framework inspired by Spring Boot.

---

## Overview

Balero is a modern PHP framework designed for developers who want structured, predictable, and maintainable backend applications.

It brings an enterprise Java/Spring Boot development style into PHP — without the complexity or bloat.

Balero combines:

* Attribute-based routing (annotation-style)
* Dependency Injection (DI)
* DTO-driven development
* JSON APIs and server-side templates
* Built-in testing with mock injection

This repository also includes a minimal CMS as a reference implementation of the framework.

---

## 🏢 Enterprise-style Architecture

Balero follows architectural patterns commonly found in enterprise frameworks like Spring Boot:

* Layered architecture (Controller / Service / DTO)
* Dependency Injection via attributes (`#[Inject]`)
* Annotation-driven configuration
* Predictable request lifecycle
* Testable components with mock injection

Designed for scalability, maintainability, and clarity.

---

## ⚡ Why Balero?

Most PHP frameworks are either:

* too unstructured
* or too “magical”

Balero offers a different approach:

* Structured like enterprise backends
* Lightweight and fast
* Minimal boilerplate
* Full control over the stack

---

## 🔥 Key Features

### 🧠 Dependency Injection

```php
#[Inject]
private AdminService $adminService;
```

* Native DI
* No container configuration
* Clean and predictable

---

### 🧩 DTO-driven development (low boilerplate)

```php
$settingsDTO = new SettingsDTO();
$settingsDTO->fromRequest($this->request);
```

* Clean request mapping
* Reduced boilerplate
* Inspired by patterns used with Project Lombok

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

* Clear structure
* Easy to read
* Predictable behavior

---

### 🔌 Hybrid Responses (HTML + JSON)

```php
#[Post('/uploader')]
#[JsonResponse]
public function postUploader() {
    return ['status' => 'ok'];
}
```

* JSON APIs
* Server-side rendering
* Flexible response model

---

### 🧪 Testing with Mock Injection

Inspired by Mockito:

```php
#[InjectMocks]
private AdminService $adminService;
```

* Built-in mocking
* Clean unit tests
* No external libraries

---

### 🔐 Attribute-driven features

```php
#[Auth(required: true)]
#[FlashStorage]
```

* Authentication
* Flash messaging
* Request handling

---

### 🧼 Zero Dependencies

* No Composer dependencies
* No external frameworks
* Full control

---

## 📦 What is included?

* Core framework (routing, DI, controllers)
* Template engine
* JSON response handling
* DTO utilities
* Testing utilities (mock injection)
* Lightweight CMS (reference implementation)

---

## 🎯 Use Cases

* REST APIs
* Lightweight CMS
* Internal tools
* Prototypes
* Structured backend systems

---

---

## 🧠 Philosophy

* No magic
* No dependencies
* Full control
* Structured development
* Enterprise patterns, simplified

---

## 🧩 Included CMS

This repository includes a minimal CMS built on top of Balero.

It serves as:

* a real-world example
* a starter project
* a reference implementation

---

## 🎯 Positioning

Balero is built for developers who:

* come from Java / enterprise environments
* are familiar with Spring Boot
* want structure instead of magic
* prefer lightweight and predictable frameworks

---

## 🐳 Docker

Balero is Docker-ready out of the box. Just run:

```bash
docker-compose up -d
```

*   **App UI**: [http://localhost:8080](http://localhost:8080)

---

## 📚 Documentation

For more detailed information, guides and API reference:

👉 [https://librixsoft.github.io/balerocms-docs/latest/](https://librixsoft.github.io/balerocms-docs/latest/)

