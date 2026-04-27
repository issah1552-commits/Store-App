# Amani Brew ERP

Amani Brew ERP is a premium, modular Enterprise Resource Planning and multi-role ordering platform tailored for Amani Brew, a high-end butchery. The system is designed to provide a seamless, conversion-focused experience for public customers while offering robust, secure management tools for internal staff and administrators.

## 🚀 Key Features

### Role-Based Access Control (RBAC)
- **Multi-tiered user roles** including Customer, Staff, Manager, and Admin.
- Custom dashboards and tailored navigation experiences based on the authenticated user's role.

### Premium User Interface
- **Modern, Conversion-Focused UI:** Designed with aesthetics in mind, featuring glassmorphism, dynamic micro-animations, and a responsive layout.
- **2-Step Registration Wizard:** A secure, intuitive onboarding flow for new customers.
- **Lucide Icons & Radix UI:** Leveraging accessible, highly customizable UI components.

### Core Modules
- **Dashboard Overview:** Centralized analytics and business metrics.
- **Inventory Management:** Full control over Product and Category catalogs.
- **User Management:** Administrative tools to manage customer and staff accounts.
- **Compliance:** Built-in Privacy Policy page designed to adhere to the Personal Data Protection Act of Tanzania.

## 🛠️ Tools & Technologies

This project is built on a modern, full-stack architecture using the following technologies:

### Backend
- **Framework:** [Laravel 12](https://laravel.com/)
- **Language:** PHP 8.2+
- **Database:** MySQL
- **Routing & State:** [Inertia.js 2.0](https://inertiajs.com/) (Server-driven client routing)

### Frontend
- **Library:** [React 19](https://react.dev/)
- **Build Tool:** [Vite](https://vitejs.dev/)
- **Styling:** [Tailwind CSS 4](https://tailwindcss.com/)
- **UI Components:** [Radix UI](https://www.radix-ui.com/)
- **Icons:** [Lucide React](https://lucide.dev/)
- **Type Checking:** TypeScript

## 📦 Installation & Setup

1. **Clone the repository**
2. **Install PHP dependencies**
   ```bash
   composer install
   ```
3. **Install Node dependencies**
   ```bash
   npm install
   ```
4. **Environment Configuration**
   Copy `.env.example` to `.env` and configure your database and environment settings.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
5. **Run Migrations & Seeders**
   ```bash
   php artisan migrate --seed
   ```

## 🖥️ Running Locally

To run the application locally, you need to start both the Laravel backend server and the Vite development server.

**Start the Laravel Server** (Running on port 8001)
```bash
php artisan serve --port=8001
```

**Start the Vite Development Server**
```bash
npm run dev
```

Alternatively, you can use the custom `composer dev` script if configured to run both concurrently.

## 📄 License
This project is open-sourced software licensed under the MIT license.
