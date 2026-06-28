# Backend Online Shop - PT. Adhikari Inovasi Indonesia (Adhivasindo)

Backend API for an e-commerce / online shop application created to fulfill the technical test (Take Home Test) at **PT. Adhikari Inovasi Indonesia (Adhivasindo)**.

This repository only contains the source code for the backend. The frontend source code can be accessed at the following link:

👉 **Frontend Repository**: [https://github.com/zaazxz/adhivasindo-test-frontend](https://github.com/zaazxz/adhivasindo-test-frontend)

---

## 🚀 Tech Stack & Versioning

This application is built using the following environment and technologies:

- **PHP**: `>= 8.2`
- **Framework**: Laravel `^11.31`
- **Database**: MySQL
- **Authentication**: JWT (JSON Web Token) via the `tymon/jwt-auth` `^2.3` package

---

## ✨ What's New (Latest Updates)

- **Comprehensive API Development**: Fully implemented Authentication, Product Management (CRUD), and Order Management APIs.
- **Order Processing & Inventory**: Added atomic inventory updates and automatic status transitions during the checkout process.
- **Product Status Workflow**: Added robust backend logic to handle `draft` and `active` product states (newly created or restocked products are automatically drafted).
- **Rich Seed Data**: Upgraded the database seeder to automatically populate 10 product categories and 20 varied sample products for a more realistic testing environment.

## 📖 API Documentation (Postman)

The complete API documentation has been exported as a **Postman Collection** to make it easy to test and integrate.

You can access and use it by following these steps:

1. Open the `public/docs/` folder in this project directory.
2. Find the file named `BE_ONLINE_SHOP_ADHIVASINDO.postman_collection.json`.
3. Open the **Postman** application, then click the **Import** button.
4. Select (or _drag-and-drop_) the JSON file.
5. The collection, along with all endpoints and environment variables, will automatically be available in your Postman workspace and ready to use.

---

## 🛠️ Installation & Setup

Follow the steps below to install and run the application on your local machine (Localhost).

### 1. Prerequisites

Make sure you have the following software installed on your machine:

- PHP >= 8.2
- Composer
- MySQL (or any other database supported by Laravel)

### 2. Installation Steps

**Clone this repository:**

```bash
git clone <YOUR_REPO_URL>
cd be-olshop-adivashindo
```

**Install PHP Dependencies:**

```bash
composer install
```

**Environment File Configuration:**
Copy the default Laravel environment template (`.env.example`) to `.env`:

```bash
cp .env.example .env
```

Open the `.env` file in your text editor and configure your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=
```

**Generate Application Key & JWT Secret Key:**

```bash
php artisan key:generate
php artisan jwt:secret
```

**Run Database Migrations and Seeders:**
This step will create the necessary tables in your database and populate them with initial dummy data.

```bash
php artisan migrate --seed
```

**Create a Storage Symlink:**
To ensure uploaded images/files (such as product photos) can be accessed publicly, run:

```bash
php artisan storage:link
```

### 3. Running the Local Server

Run the following command to start the development server:

```bash
php artisan serve
```

By default, the application will be accessible at: `http://localhost:8000`

---

## 🔐 Roles & Authentication

This application uses JWT Tokens for authentication. A valid token is required to access protected endpoints. Access rights are managed between users with the **Admin** and **Customer** roles.

- **Admin**: Has full access rights to create, edit, and delete products and product categories. Admins can also view all orders.
- **Customer**: Can only view active products, create orders (checkout), and view their own order history.

---

## 📦 Product Status Workflow

The application implements specific business logic for managing product statuses (e.g., `draft`, `active`) on the backend to ensure consistent state management:

1. **New Product Creation**: All newly created products are automatically set to `draft` status, regardless of their initial stock availability.
2. **Stock Replenishment**: If a product's stock is updated and it transitions from `out-of-stock` to `in-stock`, its status will automatically revert to `draft`.
3. **Manual Activation**: Products must be manually reviewed and activated (status changed to `active`) by an Admin before they become visible to customers.

---

## ⚠️ Note on Mocked / Dummy Features

Because this application was built specifically for a technical test, several complex real-world features are intentionally simplified and use dummy data or mocked processes:

1. **Payment Gateway Integration**: The checkout and payment process does not connect to a real payment gateway (such as Midtrans, Xendit, or Stripe). When a user creates an order, the transaction is automatically processed or uses dummy successful statuses.
2. **Shipping / Courier API**: Shipping cost calculations and courier selections are not integrated with any real third-party API (like RajaOngkir). Shipping data might be mocked or omitted.
3. **Email Notifications**: Triggers for user registration, order confirmation invoices, or password resets do not send actual emails to users.
4. **Database Seeders**: The data populated during the installation step (`php artisan db:seed`) consists of dummy users (Admin & Customer), along with 10 product categories and 20 sample products across various categories to facilitate immediate and realistic testing.

---

## 🚀 Future Improvements (Next Progress)

To make this application a complete, production-ready system, the following improvements are planned for the next development phase:

1. **API Enhancements & Additions**: Improving existing endpoints, adding deeper validation, and creating new API features to support a more comprehensive e-commerce flow.
2. **Third-Party Integrations**: Integrating with a real Payment Gateway (e.g., Midtrans/Xendit) and live Shipping/Courier APIs along with Maps integration for real-time tracking.
3. **Replacing Mock Data**: Completely replacing all dummy data and mocked processes with dynamic, real-world data handling.

---
