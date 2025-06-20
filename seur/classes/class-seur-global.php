<?php
/**
 * Add extra profile fields for users in admin
 *
 * @package  WooCommerce SEUR
 * @version  3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Seur_Global {

    /**
     * @var WC_Logger
     */
    private $log;

	public function __construct() {
		$this->log = new WC_Logger();
	}

	public function get_ownsetting() {

		if ( is_multisite() ) {

			$optionvalue = get_option( 'ownsetting' );

			if ( ! empty( $optionvalue ) ) {
				return $optionvalue;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function get_multisitesttings() {

		if ( is_multisite() ) {

			switch_to_blog( 1 );

			$optionvalue = get_option( 'multisitesttings' );

			if ( ! empty( $optionvalue ) ) {
				restore_current_blog();
				return $optionvalue;
			} else {
				restore_current_blog();
				return false;
			}
		} else {
			restore_current_blog();
			return false;
		}
	}

	public function get_option( $option ) {

		if ( is_multisite() ) {
			if ( 'ownsetting' !== $option ) {
				if ( 'hideownsetting' === $option || 'multisitesttings' === $option ) {

					switch_to_blog( 1 );

					$option_value = get_option( $option );

					if ( ! empty( $option_value ) ) {
						return $option_value;
					} else {
						restore_current_blog();
						return '';
					}
				}
			}

			$multisitesttings = $this->get_multisitesttings();
			$ownsetting       = $this->get_ownsetting();

			if ( 'yes' !== $ownsetting && 'yes' === $multisitesttings ) {
				switch_to_blog( 1 );

				$option_value = get_option( $option );

				if ( ! empty( $option_value ) ) {
					restore_current_blog();
					return $option_value;
				} else {
					restore_current_blog();
					return '';
				}
			} else {
				$option_value = get_option( $option );

				if ( ! empty( $option_value ) ) {
					return $option_value;
				} else {
					return '';
				}
			}
		}

		$option_value = get_option( $option );

		if ( ! empty( $option_value ) ) {
			return $option_value;
		} else {
			return '';
		}
	}

	public function today() {
		return gmdate( 'Ymd' );
	}

	public function save_collection( $collectionref, $type ) {
		update_option( 'seur_save_collection_' . $type, $collectionref );
	}

	public function save_reference( $reference, $type ) {
		update_option( 'seur_save_reference_' . $type, $reference );
	}

	public function save_date_normal( $date ) {
		update_option( 'seur_save_date_normal', $date );
	}

	public function save_date_cold( $date ) {
		update_option( 'seur_save_date_cold', $date );
	}

	public function get_collection( $type ) {
		return get_option( 'seur_save_collection_' . $type );
	}

	public function get_reference( $type ) {
		return get_option( 'seur_save_reference_' . $type );
	}

	public function get_date_normal() {
		return get_option( 'seur_save_date_normal' );
	}

	public function get_date_cold() {
		return get_option( 'seur_save_date_cold' );
	}

	public function cancel_collection( $type ) {
		update_option( 'seur_save_collection_' . $type, '' );
	}

	public function cancel_reference( $type ) {
		update_option( 'seur_save_reference_' . $type, '' );
	}

	public function cancel_date_normal() {
		update_option( 'seur_save_date_normal', '' );
	}

	public function cancel_date_cold() {
		update_option( 'seur_save_date_cold', '' );
	}


	public function is_test() {
		$is_test = $this->get_option( 'seur_test_field' );
		if ( 1 === (int) $is_test ) {
			return true;
		} else {
			return false;
		}
	}

	public function get_api_addres() {
		if ( $this->is_test() ) {
			return SEUR_TEST_API_ADDRESS;
		} else {
			return SEUR_LIVE_API_ADDRESS;
		}
	}

	public function get_token() {
        $token = $this->get_option( 'seur_api_token' );
        if (!$token) {
            $token = $this->seur_get_token();
        }
        return $token;
	}

	public function get_token_b() {
		$token = 'Bearer ' . $this->seur_get_token();
        return $token;
	}

    public function seur_get_token() {
        $seur_adr      = $this->get_api_addres() . SEUR_TOKEN;
        $grant_type    = 'password';
        $client_id     = $this->client_id();
        $client_secret = $this->client_secret();
        $username      = $this->client_user_name();
        $password      = $this->client_user_password();
        if ( $this->log_is_acive() ) {
            $this->slog( '$seur_adr: ' . $seur_adr );
            $this->slog( '$grant_type: ' . $grant_type );
            $this->slog( '$client_id: ' . $client_id );
            $this->slog( '$client_secret: ' . $client_secret );
            $this->slog( '$username: ' . $username );
        }
        $response      = wp_remote_post(
            $seur_adr,
            array(
                'method'      => 'POST',
                'timeout'     => 45,
                'httpversion' => '1.0',
                'user-agent'  => 'WooCommerce - Seur '.SEUR_OFFICIAL_VERSION,
                'headers'     => array(
                    'Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8',
                ),
                'body'        => array(
                    'grant_type'    => $grant_type,
                    'client_id'     => $client_id,
                    'client_secret' => $client_secret,
                    'username'      => $username,
                    'password'      => $password,
                ),
            )
        );
        $response_body = wp_remote_retrieve_body( $response );
        $result        = json_decode( $response_body );

        if (isset($result->error)) {
            $message = 'getToken Error: '.$result->error_description;
            $this->log->log(WC_Log_Levels::ERROR, $message);
            return false;
        }

        $token         = $result->access_token;

        update_option( 'seur_api_token', $token );

        return $token;
    }

	public function log_is_acive() {
		$log = $this->get_option( 'seur_log_field' );
		if ( 1 === (int) $log ) {
			return true;
		} else {
			return false;
		}
	}
	public function slog( $text ) {
		$this->log->add( 'seur', $text );
	}

	public function merchant_adress() {
		$adress = $this->get_option( 'seur_vianombre_field' ) . ' ' . $this->get_option( 'seur_vianumero_field' ) . ', ' . $this->get_option( 'seur_escalera_field' ) . ' ' . $this->get_option( 'seur_piso_field' ) . ' ' . $this->get_option( 'seur_puerta_field' );
		return $adress;
	}

	public function merchant_name() {
		$name = $this->get_option( 'seur_contacto_nombre_field' ) . ' ' . $this->get_option( 'seur_contacto_apellidos_field' );
		return $name;
	}

	public function merchant_email() {
		$email = $this->get_option( 'seur_email_field' );
		return $email;
	}

	public function client_secret() {
		$client_secret = $this->get_option( 'seur_client_secret_field' );
		return $client_secret;
	}

	public function client_user_name() {
		$client_user_name = $this->get_option( 'seur_user_field' );
		return $client_user_name;
	}

	public function client_id() {
		$client_id = $this->get_option( 'seur_client_id_field' );
		return $client_id;
	}

	public function client_user_password() {
		$user_password = $this->get_option( 'seur_password_field' );
		return $user_password;
	}
	public function clean( $out ) {
		$replace_map = array(
			'À' => 'A',
			'Ä' => 'A',
			'É' => 'E',
			'È' => 'E',
			'Ë' => 'E',
			'Í' => 'I',
			'Ì' => 'I',
			'Ï' => 'I',
			'Ó' => 'O',
			'Ò' => 'O',
			'Ö' => 'O',
			'Ú' => 'U',
			'Ù' => 'U',
			'Ü' => 'U',
			'á' => 'a',
			'à' => 'a',
			'ä' => 'a',
			'é' => 'e',
			'è' => 'e',
			'ë' => 'e',
			'í' => 'i',
			'ì' => 'i',
			'ï' => 'i',
			'ó' => 'o',
			'ò' => 'o',
			'ö' => 'o',
			'ú' => 'u',
			'ù' => 'u',
			'ü' => 'u',
			'&' => '-',
			'<' => ' ',
			'>' => ' ',
			'/' => ' ',
			'"' => ' ',
			"'" => ' ',
			'?' => ' ',
			'¿' => ' ',
		);
		return strtr( $out, $replace_map );
	}
	public function seur_date( $date ) {

		// in 09-22-2021.
		// out 2021-09-22-12:00:00.00.

		$date_2     = (string) str_replace( '/', '-', $date );
		$year       = (string) substr( $date_2, -4 );
		$month      = (string) substr( $date_2, -10, 2 );
		$day        = (string) substr( $date_2, -7, 2 );
		$final_date = (string) $year . '-' . $month . '-' . $day . '-12:00:00.000';

		if ( $this->log_is_acive() ) {
			$this->slog( '$date_2: ' . $date_2 );
			$this->slog( '$month: ' . $month );
			$this->slog( '$day: ' . $day );
			$this->slog( '$year: ' . $year );
			$this->slog( '$final_date: ' . $final_date );
		}
		return $final_date;
	}

    /**********
     * @param $url string
     * @param $header array
     * @param $data array
     * @param $action string
     * @param $queryparams bool
     * @param $file bool
     *
     * @return mixed json
     * */
    public function sendCurl($url, $header, $data, $action, $queryparams = false, $file = false)
    {
        // Prepare the args for the request
        $args = array(
            'headers' => $header,
            'timeout' => 45,
            'sslverify' => false,
            'body' => null, // We'll set this later
            'user-agent' => 'WooCommerce',
            'httpversion' => '1.0',
        );

        if ($action == 'POST') {
            if ($queryparams) {
                // For token or query params, we use URL-encoded data
                $args['body'] = implode('&', $data);
            } elseif ($file) {
                // For file upload, pass the data directly
                $args['body'] = $data;
            } else {
                // For regular POST, we send JSON-encoded data
                $args['body'] = json_encode($data);
                $args['headers']['Content-Type'] = 'application/json';
            }

            // Perform the POST request
            $response = wp_remote_post($url, $args);

        } elseif ($action == 'PUT') {
            // For PUT, we send JSON-encoded data
            $args['body'] = json_encode($data);
	        $args['method'] = 'PUT';

            // Perform the PUT request
            $response = wp_remote_request($url, $args);

        } else {
            // For other methods like GET, DELETE
            $args['method'] = $action;
            $url_with_params = $url . '?' . http_build_query($data);

            // Perform the custom request
            $response = wp_remote_get($url_with_params, $args);
        }

        // Handle the response
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->log->log(WC_Log_Levels::ERROR, "HTTP ERROR: $error_message");
            return false;
        }

        $result = wp_remote_retrieve_body($response);
        $decoded_result = json_decode($result, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded_result;
        } else {
            $this->log->log(WC_Log_Levels::ERROR, "Invalid JSON response: " . $result);
            return $result;  // Return raw result if not JSON
        }
    }

    /**
     * Seur Check City
     *
     * @param array $datos Data Array.
     */
    function seur_api_check_city( $datos ) {

        $urlws = $this->get_api_addres() . SEUR_API_CITIES;

        if ( ! seur_api_check_url_exists( $urlws ) ) {
            die( esc_html__( 'We&apos;re sorry, SEUR API is down. Please try again in few minutes', 'seur' ) );
        }

        $token = $this->get_token_b();
        if (!$token)
            return false;

        $headers = [
            'Content-Type' => 'application/json;charset=UTF-8',
            'Accept' => 'application/json',
            'Authorization' => $token,
        ];

        $data = [
            'countryCode' => $datos['countryCode'],
            'postalCode' => $datos['postalCode']
        ];

        $response = $this->sendCurl($urlws, $headers, $data, "GET");

        if (isset($response->errors)) {
            $this->slog('seur_api_check_city Error: '.$response->errors[0]->detail);
            return false;
        }

        if (!isset($response['data'])) {
            return false;
        }
        return $response['data'];
    }

    public function prepareDataShipmentAux($id_order, $label_data)
    {
        $preparedData = $label_data;

        $servicio = $preparedData['seur_service'];
        $producto = $preparedData['seur_product'];
        $mercancia = false;

        if ($this->isInternationalShipping($preparedData['paisgl'])) {
            $mercancia = true;
        }

        $total_weight = (float)$preparedData['customer_weight_kg'];
        $total_packages = $preparedData['total_bultos'];

        if($total_weight == 0) $total_weight = 1;
        if($total_packages == 0 || $servicio==77) $total_packages = 1;

        $pesoBulto = $total_weight / $total_packages;
        if ($pesoBulto < 1) { //1kg
            $pesoBulto = 1;
            $total_weight = $total_packages;
        }

        $preparedData['notification'] = $preparedData['reparto_notificar']; // CORRECTO ?
        $preparedData['advice_checkbox'] = $preparedData['preaviso_notificar'];
        $preparedData['distribution_checkbox'] = $preparedData['tipo_aviso'];
        $preparedData['servicio'] = $servicio;
        $preparedData['producto'] = $producto;
        $preparedData['mercancia'] = $mercancia;
        $preparedData['total_weight'] = $total_weight;
        $preparedData['total_packages'] = $total_packages;
        $preparedData['pesoBulto'] = $pesoBulto;

        return $preparedData;
    }

    public function prepareDataShipment($id_order, $label_data, &$preparedData)
    {
        $preparedData = $this->prepareDataShipmentAux($id_order, $label_data);
        if (!$preparedData) {
            return false;
        }
        $parcels = [];
        for ($i = 1; $i <= (float)$preparedData['total_packages']; $i++) {
            $parcels[] = [
                "weight" => $preparedData['pesoBulto'],
                "width" => 1,
                "height" => 1,
                "length" => 1,
                "parcelReference" => "BULTO_".$i
            ];
        }

        $data = [
            "serviceCode" => $preparedData['servicio'],
            "productCode" => $preparedData['producto'],
            "charges" => "P",
            "ecommerceName" => "WooCommerce",
            "security" => false,
            //"pod" => "S",
            "dConsig" => false,
            "did" => false,
            "dSat" =>  false,
            "change" => $label_data['changeService']?true:false,
            /*"aduOutKey" =>  "P",
            "aduInKey" =>  "P",
            "customsGoodsType" => "C",
            "agdReference" => "",*/
            "reference" => $preparedData['order_id_seur'],
            "receiver" => [
                "name" => $preparedData['customer_first_name'] . ' '. $preparedData['customer_last_name'],
                "idNumber" => '',
                "phone" => $preparedData['customer_phone']??'',
                "email" => $preparedData['customer_email'],
                "contactName" => $preparedData['customer_first_name'] . ' '. $preparedData['customer_last_name'],
                "address" => [
                    "streetName" => substr($preparedData['customer_address_1'] . ' ' . $preparedData['customer_address_2'], 0, SHIPMENT_STREETNAME_LENGTH),
                    "postalCode" => $preparedData['customer_postcode'],
                    "country" => $preparedData['customer_country'],
                    "city" => ($preparedData['customer_city']??'')
                ]
            ],
            "sender" => [
                "name" =>  $preparedData['empresa'],
                "idNumber" => $preparedData['nif'],
                "phone" => $preparedData['telefono'],
                "accountNumber" => $preparedData['ccc'].'-'.$preparedData['franquicia'],
                "email" => $preparedData['email'],
                "contactName" => $preparedData['contacto_nombre'] . ' ' . $preparedData['contacto_apellidos'],
                "address" => [
                    "streetName" => substr($preparedData['viatipo'].' '.
                        $preparedData['vianombre'].' '.
                        $preparedData['vianumero'],
                        0, SHIPMENT_STREETNAME_LENGTH),
                    "postalCode" => $preparedData['postalcode'],
                    "country" => $preparedData['paisgl'],
                    "cityName" => $preparedData['poblacion']
                ]
            ],
            "parcels" => $parcels
        ];

        $comments = [];
        if (($preparedData['producto'] == 18 && $preparedData['customer_country'] == 'ES' ) ||
            ($preparedData['producto'] == 114 && $preparedData['customer_country'] == 'FR')) {
            $comments[] = 'ENTREGA: ' . $this->getDeliveryDate();
            $comments[] = 'TIPO: ' . $this->getShipmentType($id_order);
        }
        $comments[] = $preparedData['customer_order_notes'];

        $data['comments'] = substr(implode('; ', $comments), 0, SHIPMENT_COMMENT_LENGTH);

        if (isset($preparedData['cod_centro']) && $preparedData['cod_centro']) {
            $data["receiver"]["address"]["pickupCentreCode"] = $preparedData['pudoId'];
        }

        $order = wc_get_order($id_order);
        if ($this->isInternationalShipping($preparedData['paisgl']) &&
            !$this->isEuropeanShipping($preparedData['paisgl'])) {
            $data['taric'] = get_option('seur_taric_field');
            $data['declaredValue'] = [
                "currencyCode" => "EUR",
                "amount" => $order->get_total()
            ];
        }

        if ( 'cod' === $preparedData['order_pay_method']) {
            $data['codValue'] = [
                "currencyCode" => "EUR",
                "amount" => $preparedData['valorReembolso'],
                "codFee" => "P"
            ];
        }

        return $data;
    }

    public function isInternationalShipping($country_iso_code) {
        return $country_iso_code != 'ES' && $country_iso_code != 'PT' && $country_iso_code != 'AD';
    }

    public function isEuropeanShipping($country_iso_code) {
        $european_countries = [
            'AT' => 'Austria',
            'BE' => 'Bélgica',
            'BG' => 'Bulgaria',
            'CY' => 'Chipre',
            'CZ' => 'República Checa',
            'DE' => 'Alemania',
            'DK' => 'Dinamarca',
            'EE' => 'Estonia',
            'ES' => 'España',
            'FI' => 'Finlandia',
            'FR' => 'Francia',
            'GR' => 'Grecia',
            'HR' => 'Croacia',
            'HU' => 'Hungría',
            'IE' => 'Irlanda',
            'IT' => 'Italia',
            'LT' => 'Lituania',
            'LU' => 'Luxemburgo',
            'LV' => 'Letonia',
            'MT' => 'Malta',
            'NL' => 'Países Bajos',
            'PL' => 'Polonia',
            'PT' => 'Portugal',
            'RO' => 'Rumanía',
            'SE' => 'Suecia',
            'SI' => 'Eslovenia',
            'SK' => 'Eslovaquia'
        ];
        $european_countries = array_keys($european_countries);
        return in_array($country_iso_code, $european_countries);
    }

    /**
    * Get delivery date for the current shipment:
    * - Sunday through Friday: next day
    * - Saturday: Monday
    *
    * @return string
    */
    public function getDeliveryDate()
    {
        $deliveryDate = new DateTime('tomorrow');
        $deliveryDay = strtolower(gmdate('l', $deliveryDate->getTimestamp()));
        if ($deliveryDay == 'sunday') {
            $deliveryDate->add(new \DateInterval('P1D'));
        }
        return $deliveryDate->format('d/m/Y');
    }

    /**
     * @param $order
     * @return null
     */
    public function getShipmentType($id_order)
    {
        $orderProductTypes = [];
        $type = new ProductType();
        $productTypes = $type->getValues();
        $isOrderFood = true;
        $order = wc_get_order($id_order);
        foreach ( $order->get_items() as $item_id => $item ) {
            $product =$item->get_product();
            if ($seurProductType = $product->get_attribute( 'pa_'.ProductType::PRODUCT_TYPE_ATTRIBUTE_CODE )) {
                $orderProductTypes[$seurProductType] = true;
                if ($seurProductType == ProductType::PRODUCT_TYPE_OTHER) {
                    $isOrderFood = false;
                }
            } else {
                $orderProductTypes[ProductType::PRODUCT_TYPE_OTHER] = true;
                $isOrderFood = false;
            }
        }
        $orderProductTypes = array_keys($orderProductTypes);
        if (count($orderProductTypes) === 1) {
            $shipmentType = reset($orderProductTypes);
        } elseif ($isOrderFood) {
            $shipmentType = ProductType::PRODUCT_TYPE_FOOD_OTHER;
        } else {
            $shipmentType = ProductType::PRODUCT_TYPE_OTHER;
        }
        return $shipmentType;
    }

    public function addShipment($preparedData)
    {
        try
        {
            $url = $this->get_api_addres() . SEUR_API_SHIPMENT;
            $token = $this->get_token_b();
            if (!$token || empty($token))
                return false;

            $headers = [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Accept' => 'application/json',
                'Authorization' => $token,
            ];

            $response = $this->sendCurl($url, $headers, $preparedData, "POST");

            $message = '';
            if (isset($response['errors'])) {
                $message = 'addShipment Error: '.$response['errors'][0]['detail'];
            }

            if (isset($response['error'])) {
                $message = 'addShipment Error: '.$response['error'];
            }

            if (is_string($response)) {
                $message = 'addShipment Error: '.$response;
            }

            if (!$message) {
                $this->log->log(WC_Log_Levels::INFO, "addShipment Created OK");
                return [ 'status' => true,
                    'response' => $response] ;
            }

            $this->log->log(WC_Log_Levels::ERROR, $message);
            return [ 'status'=> false,
                'message' => $message ];
        }
        catch (Exception $e)
        {
            $message = 'ADD SHIPMENTS - ' . $e->getMessage();
            $this->log->log(WC_Log_Levels::ERROR, $message);
            return [ 'status'=> false,
                'message' => $message ];
        }
    }

    public function updateShipment($preparedData)
    {
        try {
            $url = $this->get_api_addres() . SEUR_API_SHIPMENT_UPDATE;
            $token = $this->get_token_b();
            if (!$token || empty($token)) {
                set_transient('updateShipment_notice', 'Error Update Shipment: Token not found');
                return false;
            }

            $headers = [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Accept' => 'application/json',
                'Authorization' => $token,
            ];

            $status = true;
            $message = 'Shipment Updated OK';

            $response = $this->sendCurl($url, $headers, $preparedData, "PUT");
            if (isset($response['errors'])) {
                $status = false;
                $message = 'Update Shipment Error: '.$response['errors'][0]['detail'];
            }

            if (isset($response['error'])) {
                $status = false;
                $message = 'Update Shipment Error: '.$response['error'];
            }

            if (is_string($response)) {
                $status = false;
                $message = 'Update Shipment Error: '.$response;
            }
        } catch (Exception $e) {
            $status = false;
            $message = 'Error Update Shipment Exception - ' . $e->getMessage();
        }

        $this->log->log($status?WC_Log_Levels::INFO:WC_Log_Levels::ERROR, $message);
        set_transient('updateShipment_notice', $message);
        return $status;
    }

	public function addParcelsToShipment($shipmentCode, $totalWeight, $numNewParcels, $totalParcels) {
		try {
			$url = $this->get_api_addres() . SEUR_API_ADD_PARCELS;
			$token = $this->get_token_b();

			if (!$token || empty($token)) {
				set_transient('addParcels_notice', 'Add Parcels Error: Token not found');
				return false;
			}

			$headers = [
				'Content-Type' => 'application/json;charset=UTF-8',
				'Accept' => 'application/json',
				'Authorization' => $token,
			];

			// Calcular peso promedio por paquete
			$parcelWeight = round($totalWeight / $totalParcels, 2);
			$newParcels = [];
			for ($i = $totalParcels-$numNewParcels+1; $i <= $totalParcels; $i++) {
				$newParcels[] = [
                    "weight" => $parcelWeight,
                    "parcelReference" => "BULTO_".$i
                ];
			}

			$data = [
				"shipmentCode" => $shipmentCode,
				"parcels" => $newParcels
			];

			return $this->sendCurl($url, $headers, $data, "PUT");

		} catch (Exception $e) {
			set_transient('addParcels_notice', 'Add Parcels Exception Error - ' . $e->getMessage());
			return false;
		}
	}

    function isPdf() {
        return strtolower(get_option('seur_tipo_etiqueta_field')) != strtolower(PrinterType::PRINTER_TYPE_ETIQUETA);
    }

    public function getLabel($response, $is_pdf, $label_data, $order_id)
    {
	    global $wp_filesystem;
	    WP_Filesystem();

	    if ( is_array($response) && isset($response['response'])) {
            $response = $response['response'];
        }

        try
        {
            $urlws = $this->get_api_addres(). SEUR_API_LABELS;

            $token = $this->get_token_b();
            if (!$token)
                return false;

            $headers = [
                'Content-Type' => 'application/json;charset=UTF-8',
                'Accept' => 'application/json',
                'Authorization' => $token,
            ];

            $type = new PrinterType();
            $types = $type->getOptions();
            $printerType = $types[get_option('seur_tipo_etiqueta_field')];

			// For ZPL labels, when more than one package, merge all labels into a single file.
			$merge_labels = !$is_pdf;

            $data = [
                'code' => $response['data']['shipmentCode'],
                'type' => $printerType,
                'entity' => 'EXPEDITIONS'
            ];
            if ($printerType == $types[PrinterType::PRINTER_TYPE_A4_3]) {
                $data['templateType'] = PrinterType::TEMPLATE_TYPE_A4_3;
            }

            $responseLabel = $this->sendCurl($urlws, $headers, $data, "GET");

            if (isset($responseLabel['errors'])) {
                $message = 'getLabel Error: '.$responseLabel['errors'][0]['detail'];
                $this->log->log(WC_Log_Levels::ERROR, $message);
                return [ 'status'=> false,
                    'message' => $message ];
            }

            $upload_dir = seur_upload_dir( 'labels' );

            if (! $wp_filesystem->is_writable($upload_dir)) {
                $message = 'getLabel Error: '.$upload_dir . ' is NOT writable';
                $this->log->log(WC_Log_Levels::ERROR, $message);
                return [ 'status'=> false,
                    'message' => $message ];
            }

            $label_files = [];
            $seur_label = [];
            $cont = 1;

            // Generate file/s with then content of the labels
            foreach ($responseLabel['data'] as $data) {
                if ($is_pdf) {
                    $content = base64_decode($data['pdf']);
                } else {
                    $content = $data['label'];
                }

				// When merging labels, all labels are written to the same file. A suffix is added in other case.
	            $label_file = 'label_order_id_' . $order_id . '_' . gmdate( 'd-m-Y' );
				if ( !$merge_labels ) {
					$label_file .= ($cont == 1 ? '' : '_' . $cont);
				}
				$label_file .= ($is_pdf ? '.pdf' : '.txt');

                $upload_path = $upload_dir . '/' . $label_file;

                if ($merge_labels) {
                    $existing_content = $wp_filesystem->get_contents($upload_path);
                    if ($cont == 1) {
                        $existing_content = '';
                    }
                    $content = $existing_content . $content;
                }

                if ( ! $wp_filesystem->put_contents( $upload_path, $content, FS_CHMOD_FILE ) ) {
		            $message = 'getLabel Error file_put_contents: ' . $upload_path;
		            $this->log->log( WC_Log_Levels::ERROR, $message );
		            return [
			            'status'  => false,
			            'message' => $message,
		            ];
	            }
                if (!in_array($label_file, $label_files)) {
                    $label_files[] = $label_file;
                }

	            $cont++;
            }

            $labelids_old = seur_get_labels_ids($order_id);
            // Delete old labels if they are not the same type that the configured one
            foreach ($labelids_old as $labelid_old) {
                if (get_post_meta($labelid_old, '_seur_label_type', true) != get_option('seur_tipo_etiqueta_field')) {
                    wp_delete_post($labelid_old, true);
                }
            }
            // Generate a 'seur_labels' post for each physical file generated
            foreach ($label_files as $label_file) {
                //Create post
                $labelid = wp_insert_post(
                    array(
                        'post_title'     => 'Label Order ID ' . $order_id,
                        'post_type'      => 'seur_labels',
                        'post_status'    => 'publish',
                        'ping_status'    => 'closed',
                        'comment_status' => 'closed',
                        'tax_input'      => array(
                            'labels-product' => $label_data['seur_shipping_method'],
                        ),
                    )
                );
                $update_post = array(
                    'ID'         => $labelid,
                    'post_title' => 'Label ' . $label_data['order_id_seur'] . '( ID #' . $labelid . ' )',
                );
                wp_update_post( $update_post );

                //Update post label metas
                $upload_path = $upload_dir . '/' . $label_file;
                update_post_meta( $labelid, '_seur_shipping_id_number', $label_data['order_id_seur'] );
                update_post_meta( $labelid, '_seur_shipping_method', $label_data['seur_shipping_method'] );
                update_post_meta( $labelid, '_seur_shipping_weight', $label_data['customer_weight_kg'] );
                update_post_meta( $labelid, '_seur_shipping_packages', $label_data['total_bultos'] );
                update_post_meta( $labelid, '_seur_shipping_product', $label_data['seur_product'] );
                update_post_meta( $labelid, '_seur_shipping_service', $label_data['seur_service'] );
                update_post_meta( $labelid, '_seur_shipping_ccc', $label_data['ccc'] );
                update_post_meta( $labelid, '_seur_shipping_order_id', $order_id );
                update_post_meta( $labelid, '_seur_shipping_order_customer_comments', $label_data['customer_order_notes'] );
                update_post_meta( $labelid, '_seur_shipping_order_label_file_name', $label_file );
                update_post_meta( $labelid, '_seur_shipping_order_label_path_name', $upload_path );
                update_post_meta( $labelid, '_seur_label_customer_name', $label_data['customer_first_name'] . ' ' . $label_data['customer_last_name'] );
                update_post_meta( $labelid, '_seur_label_type', $printerType );

                $result = true;
                $message = 'OK';
                if (! $labelid ) {
                    $result = false;
                    $message = $responseLabel['out']['mensaje'];
                }
                $seur_label[] = [
                    'result' => $result,
                    'labelID' => $labelid,
                    'message' => $message
                ];
                $labelids[] = $labelid;
            }

            // Update post order metas
            $order = seur_get_order($order_id);
            $order->update_meta_data('_seur_shipping_id_number', $label_data['order_id_seur'] );
            $order->update_meta_data('_seur_label_id_number', $labelids);
            $order->update_meta_data( '_seur_shipping_order_label_downloaded', 'yes');
            $order->save_meta_data();

            $expeditionCode = $response['data']['shipmentCode'];
            $ecbs = $response['data']['ecbs'];
            $parcelNumbers = $response['data']['parcelNumbers'];

            $this->log->log(WC_Log_Levels::INFO, "getLabel OK");
            return [
                'status' => true,
                'ecbs' => $ecbs,
                'parcelNumbers' => $parcelNumbers,
                'expeditionCode' => $expeditionCode,
                'label_files' => $label_files,
                'label_ids' => $labelids,
                'seur_label' => $seur_label
            ];
        }
        catch (Exception $e)
        {
            $message = 'getLabel Exception - ' . $e->getMessage();
            $this->log->log(WC_Log_Levels::ERROR, $message);
            return [ 'status'=> false,
                'message' => $message ];
        }
    }

    public static function createPickupIfAuto($merchant_data, $id_order) {
        $make_pickup = true;
        $auto = (get_option('seur_activate_local_pickup_field') == 1); //Automatico

        /* TODO PENDIENTE DE LA MIGRACIÓN DE PICKUPS
        // Pickup yet generated?
        $pickup_data = SeurPickup::getLastPickup($merchant_data['id_seur_ccc']);
        if (!empty($pickup_data)) {
            $datepickup = explode(' ', $pickup_data['date']);
            $datepickup = $datepickup[0];
            if (strtotime(gmdate('Y-m-d')) == strtotime($datepickup))
                $make_pickup = false;
        }
        if ($make_pickup && $auto) {
            return SeurPickup::createPickup($merchant_data['id_seur_ccc'], null, $id_order);
        }*/
    }

    /**
     * Get the products
     *
     * @return array
     */
    public function get_products() {
        include_once SEUR_DATA_PATH . 'seur-products.php';
        return get_seur_product();
    }
    /**
     * Get servicio
     *
     * @param string $real_name Service Name.
     */
    public function get_servicio( $real_name ) {

        $registros = $this->get_products();

        foreach ( $registros as $description => $valor ) {
            if ( $real_name === $description ) {

                $data = array(
                    'country' => $valor['pais'],
                    'service' => $valor['service'],
                    'product' => $valor['product'],
                );
                return $data;
            }
        }
        return false;
    }

    public function is_seur_order($order_id) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query required, no core function available
        $result = $wpdb->get_results(
            $wpdb->prepare(
            "SELECT DISTINCT o.order_id 
        FROM {$wpdb->prefix}woocommerce_order_items o 
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta om ON om.order_item_id = o.order_item_id 
        WHERE om.meta_key = %s AND om.meta_value LIKE %s 
        AND o.order_id = %d
        UNION
        SELECT DISTINCT p.ID
        FROM {$wpdb->prefix}posts p
        INNER JOIN {$wpdb->prefix}postmeta m ON m.post_id = p.ID
        WHERE post_type = %s
        AND meta_key LIKE %s
        AND ID = %d",
            [
                'method_id',
                '%seur%',
                $order_id,
                'shop_order',
                '_seur_shipping%',
                $order_id
            ]
        ));
        //var_dump($wpdb->last_query); die;
        return !empty($result);
    }

    public function is_seur_local_method($custom_rate_id) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query required, no core function available
        return $wpdb->get_results($wpdb->prepare(
            "SELECT ID
            FROM {$wpdb->prefix}seur_custom_rates
            where rate like %s and ID = %d",
            ['%2SHOP', $custom_rate_id])
        );
    }


    public function getCountries($countries) {
        $allCountries = seur_get_countries();
        $aroundESCountries = seur_get_countries_around_ES();
        $UECountries = seur_get_countries_EU();
        if ($countries[0] == 'OUT-EU') {
            return array_diff($allCountries, $UECountries);
        }
        if ($countries[0] == 'INTERNATIONAL') {
            return $allCountries;
        }
        foreach ($countries as $code_country) {
            if (array_key_exists($code_country, $aroundESCountries)) {
                $result[$code_country] = $aroundESCountries[$code_country];
            }
        }
        return $result;
    }

    public function getStates( $country, $states ) {

        $country_states = seur_get_countries_states($country);
        if (!$country_states) {
            return false;
        }
        asort( $country_states );
        foreach ($states as $state) {
            if ($state == 'all') {
                $result = $country_states;
            } else {
                $result[$state] = $country_states[$state];
            }
        }
        return $result;
    }

    /**
     * Check if the order has a label
     * @param $post_order_int WC_Order|WP_Post|int
     *
     * @return bool
     */
    public function has_label($post_order_int) {
        $order = seur_get_order($post_order_int);
        $label_ids = seur_get_labels_ids( $order->get_id() );
        return (!empty($label_ids));
    }

    public function seur_download_rates_csv() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'seur_custom_rates';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery -- Table name is hardcoded and safe
        $rates = $wpdb->get_results( "SELECT * FROM {$table_name}", ARRAY_A );

        if ( empty( $rates ) ) {
            wp_die( 'No hay tarifas para exportar.' );
        }

        // Limpiar el buffer de salida para evitar HTML no deseado
        ob_clean();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=seur_tarifas_actuales.csv' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        // Abrir salida para CSV
        $output = fopen( 'php://output', 'w' );

        // Reemplazar los saltos de línea en los códigos postales para exportar
        // Eliminar las columnas "created_at" y "updated_at"
        foreach ( $rates as &$row ) {
            $row['postcode'] = str_replace("\r\n", "|", $row['postcode']);
            unset( $row['created_at'], $row['updated_at'] );
        }
        unset($row); // Para evitar referencias inesperadas

        // Escribir encabezados sin las columnas eliminadas
        fputcsv( $output, array_keys( $rates[0] ) );

        // Escribir filas sin las columnas eliminadas
        foreach ( $rates as $row ) {
            fputcsv( $output, $row );
        }

        // Cerrar salida
        fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output is not a real file and WP_Filesystem is not applicable

        // Detener la ejecución de WordPress
        exit;
    }
}

function seur() {
	return new Seur_Global();
}
