<?php 

/*-----------------[Start - function | Template for header]  ----------------*/
function template_header($title, $num_items_in_cart = 0)
{
    // Get the number of items in the cart
    $num_items_in_cart = isset($_SESSION['cart_items']) ? count($_SESSION['cart_items']) : 0;

    // Get the user's name from the session
    $user_name = isset($_SESSION['user_info']['user_name']) ? htmlspecialchars($_SESSION['user_info']['user_name'], ENT_QUOTES, 'UTF-8') : '';

    // Start of heredoc for static HTML parts
    $html = <<<EOT
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        
        <title>$title</title>
        
        <!-- Include Bootstrap CSS via CDN -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        
        <!-- Include Custom CSS in a separate file -->
        <link rel="stylesheet" type="text/css" href="style.css">

        <!-- Include Print CSS in a separate file -->
        <link rel="stylesheet" type="text/css" href="print.css" media="print">
        
        <!-- Font Awesome Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    </head>
    
    <body>
        <!-- ------------------[Start: Top Navigation Bar]----------------------------- -->
        <header>
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
                <div class="container-fluid">
                    <a class="navbar-brand" href="index.php"><img src="images/company_logo/logo.png" alt="Company Logo" height="40"></a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item"><a class="nav-link" href="index.php?page=products">Products</a></li>
                            <li class="nav-item"><a class="nav-link" href="#id_footer">Contact</a></li>
                        </ul>
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                            <li class="nav-item position-relative">
                                <a class="nav-link" href="index.php?page=cart">
                                    <i class="fa fa-shopping-cart"></i> My Cart
                                    <!-- Show number of items in the customer's purchase cart -->
                                    <span class="cart-badge position-absolute top-0 start-100 translate-middle">
                                        $num_items_in_cart
                                    </span>
                                </a>
                            </li>
EOT;

    if (isset($_SESSION['user_info']['user_name'])) {
        // Determine user role and display appropriate profile link
        $role_name = isset($_SESSION['user_info']['role_name']) ? $_SESSION['user_info']['role_name'] : '';

        if ($role_name == 'admin') {
            $profile_link = 'index.php?page=admin_dashboard';
        } elseif ($role_name == 'manager') {
            $profile_link = 'index.php?page=manager_dashboard';
        } elseif ($role_name == 'customer') {
            $profile_link = 'index.php?page=user_dashboard';
        } else {
            $profile_link = '#'; // Fallback in case the role is undefined
        }

        $html .= <<<EOT
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle px-3" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa fa-toggle-down"></i>
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="$profile_link">Profile</a></li>
                    <li><a class="dropdown-item" href="index.php?page=logout">Logout</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php?page=login">
                    <i class="fa fa-fw fa-user"></i> User ($user_name)
                </a>
            </li>
EOT;
    } else {
        $html .= <<<EOT
            <li class="nav-item">
                <a class="nav-link" href="index.php?page=login">
                    <i class="fa fa-fw fa-user"></i> User
                </a>
            </li>
EOT;
    }

    // End the HTML structure
    $html .= <<<EOT
                        </ul>
                    </div>
                </div>
            </nav>
        </header>
        <!-- ------------------[End: Top Navigation Bar]----------------------------- -->
        <main class="main-container-wrapper my-1">
            <div class="content container py-2">
EOT;

    // Output the entire HTML content
    echo $html;
}
/*-----------------[End - function | Template for header]  ------------------*/


/*-----------------[Start - function | Template for footer]  ----------------*/
function template_footer()
{
	$year = date('Y');

	echo <<<EOT
			</div>
	  	</main>
	  	<!-- ------------------[End: main content of the page]--------------------- -->

	  	<!-- ------------------[Start: Footer]----------------------------- -->
	  	<footer id="id_footer" class="bg-dark text-white text-center py-4">
			<div class="container">
				<p>&copy; $year Dannoruwa-Anush. All rights reserved.</p>
				<p>Contact us: 011 111 2222 </p>
            	<p>Address: Online Hardware, No 11, ABC Road, Colombo</p>
			</div>
	  	</footer>
	  	<!-- ------------------[End: Footer]----------------------------- -->

	  	<!-- Include Bootstrap JS Via CDN -->
	  	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>

	</body>
	</html>
EOT;
}
/*-----------------[End - function | Template for footer]  ------------------*/

?>