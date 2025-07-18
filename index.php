<?php

session_start();

include("templates.php");

/*----------------- [Start - Define Allowed Pages and Roles] ----------------*/
// Define paths for pages
$allowed_pages = [
   'home' => 'home.php',
   'product' => 'product.php',
   'products' => 'products.php',

   'add_to_cart' => 'add_to_cart.php',
   'cart' => 'cart.php',
   'order_confirmation' => 'order_confirmation.php',

   // user_login/
   'login' => 'user_login/login.php',
   'logout' => 'user_login/logout.php',
   'registration' => 'user_login/registration.php',

   'admin_dashboard' => 'user_login/admin_dashboard.php',
   'user_dashboard' => 'user_login/user_dashboard.php',
   'manager_dashboard' => 'user_login/manager_dashboard.php',

   // user_login/common_dashboard_components
   'user_profile' => 'user_login/common_dashboard_components/user_profile.php',
   'employee_info' => 'user_login/common_dashboard_components/employee_info.php',
   'category_info' => 'user_login/common_dashboard_components/category_info.php',
   'subcategory_info' => 'user_login/common_dashboard_components/subcategory_info.php',
   'product_info' => 'user_login/common_dashboard_components/product_info.php',

   // user_login/common_dashboard_components/order
   'order_info' => 'user_login/common_dashboard_components/order/order_info.php',
   'order_view' => 'user_login/common_dashboard_components/order/order_view.php',
   'export_invoice_pdf' => 'user_login/common_dashboard_components/order/export_invoice_pdf.php',

   // user_login/common_dashboard_components/report
   'report_info' => 'user_login/common_dashboard_components/report/report_info.php',
   'export_report_pdf' => 'user_login/common_dashboard_components/report/export_report_pdf.php',

   // order tabs
   'pending_orders' => 'user_login/common_dashboard_components/order/order_tabs/pending_orders.php',
   'shipped_orders' => 'user_login/common_dashboard_components/order/order_tabs/shipped_orders.php',
   'delivered_orders' => 'user_login/common_dashboard_components/order/order_tabs/delivered_orders.php',
   'cancelled_orders' => 'user_login/common_dashboard_components/order/order_tabs/cancelled_orders.php',
];

// Define which roles can access each page
$page_roles = [
   // Public pages
   'home' => [],
   'login' => [],
   'logout' => [],
   'registration' => [],
   'product' => [],
   'products' => [],
   'add_to_cart' => [],
   'cart' => [],
   'order_confirmation' => [],

   // Role-specific dashboards
   'user_dashboard' => ['customer'],
   'manager_dashboard' => ['manager'],
   'admin_dashboard' => ['admin'],

   // Shared components
   'user_profile' => ['customer', 'manager', 'admin'],
   'employee_info' => ['manager', 'admin'],
   'category_info' => ['manager', 'admin'],
   'subcategory_info' => ['manager', 'admin'],
   'product_info' => ['manager', 'admin'],

   'order_info' => ['customer', 'manager', 'admin'],
   'order_view' => ['customer', 'manager', 'admin'],
   'export_invoice_pdf' => ['customer', 'manager', 'admin'],

   'report_info' => ['customer', 'manager', 'admin'],
   'export_report_pdf' => ['customer', 'manager', 'admin'],

   'pending_orders' => ['customer', 'manager', 'admin'],
   'shipped_orders' => ['customer', 'manager', 'admin'],
   'delivered_orders' => ['customer', 'manager', 'admin'],
   'cancelled_orders' => ['customer', 'manager', 'admin'],
];

/*----------------- [End - Define Allowed Pages and Roles] ----------------*/


/*----------------- [Start - Page Routing] ----------------*/

// Default page
$page_key = $_GET['page'] ?? 'home';

// Prevent logged-in users from accessing login and registration
$public_auth_pages = ['login', 'registration'];
if (in_array($page_key, $public_auth_pages) && isset($_SESSION['user_info'])) {
   $role = $_SESSION['user_info']['role_name'] ?? '';
   $dashboardMap = [
      'admin' => 'admin_dashboard',
      'manager' => 'manager_dashboard',
      'cashier' => 'cashier_dashboard', 
      'customer' => 'user_dashboard'
   ];
   $redirectPage = $dashboardMap[$role] ?? 'home';
   header("Location: index.php?page=$redirectPage");
   exit();
}

// Check if the requested page exists
if (array_key_exists($page_key, $allowed_pages)) {
   $required_roles = $page_roles[$page_key] ?? [];

   // If roles are required for the page
   if (!empty($required_roles)) {
      // Check if user is logged in and has a valid role
      if (!isset($_SESSION['user_info']) || !in_array($_SESSION['user_info']['role_name'], $required_roles)) {
         session_unset();
         session_destroy();
         header("Location: index.php?page=login");
         exit();
      }
   }

   // Include the allowed page
   include($allowed_pages[$page_key]);
} else {
   include('404.php');
}

/*----------------- [End - Page Routing] ----------------*/
