<?php

require_once(__DIR__ . '/../repositories/Publisher.php');

// Allow Ajax calls only
notAjaxRequest();

// Assessing coming request	
switch ($_SERVER["REQUEST_METHOD"]) 
{
    case "POST":
			if(!$_POST['name']) 
			{
				http_response_code(400);
				die(json_encode("Product name is missing"));				
			}

			// Publish product to RabbitMQ queue "add_products"
			$publisher = new Publisher('products', 'add_products');
			$publisher->initiateConnection();
			$publisher->pushMessage($_POST);
			$publisher->terminateConnection();
			
			die(json_encode(['status' => 'success', 'data' => 'Request was processed successfully']));
        break;

    default:
        http_response_code(404);
		die(json_encode("Not found"));
}

/**
* Check if request is not Ajax
**/
function notAjaxRequest() 
{
	if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') 
	{
        http_response_code(403);
		die(json_encode("Not allowed"));
	}
}
?>