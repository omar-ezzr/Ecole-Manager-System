# Ecole Manager

Ecole Manager is a Laravel 12 web application for managing school data.

The application helps organize student records, health records, semester grades, Excel imports, dashboard statistics, authentication, settings pages, and QR code generation.

This version is the first GitHub release of the project: **v1.0.0**.

## Project Purpose

The goal of this project is to provide a simple and organized school management system that allows administrators and users to manage important student-related data from one place.

The system focuses on:

- Student records
- Health records
- Semester grades
- Excel template downloads
- Excel data imports
- Dashboard statistics
- Chart visualizations
- QR code generation
- User authentication and settings

## Tech Stack

- Laravel 12
- PHP 8.2+
- Blade
- Livewire 3
- Livewire Flux
- Tailwind CSS
- Vite
- Laravel Eloquent
- Laravel Sanctum
- Spatie Laravel Permission
- PhpSpreadsheet
- Chart.js
- Simple QR Code

## Main Features

- User authentication
- Email verification
- Password reset
- Profile settings
- Password settings
- Student management
- Health record management
- Semester grade management
- Search and filtering
- Dashboard cards and statistics
- Chart.js visualizations
- Excel import system
- Excel template downloads
- QR code generation for student details
- Admin access using the configured `EMAIL_AUTH` value

## Project Structure

```text
app/
  Http/Controllers/       Laravel controllers
  Livewire/               Livewire auth and settings components
  Models/                 Eloquent models
  View/Components/        Blade chart and table components

database/
  migrations/             Database schema files
  seeders/                Initial seed data

resources/
  views/                  Blade pages and Livewire views
  css/                    Application styles
  js/                     JavaScript entry files

routes/
  web.php                 Main web routes
  auth.php                Authentication routes
  api.php                 API routes

public/
  js/chart.js             Chart.js asset
