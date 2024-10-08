<?php

class EcwidProductApi {

    var $store_id = '';

    var $error = '';

    var $error_code = '';

    var $ECWID_PRODUCT_API_ENDPOINT = '';

    function __construct($store_id) {

        $this->ECWID_PRODUCT_API_ENDPOINT = 'https://app.ecwid.com/api/v1';
        $this->store_id = intval($store_id);
    }

    function process_request($url) {

        $result = false;
        $fetch_result = EcwidPlatform::fetch_url($url);

        if ($fetch_result['code'] == 200) {
            $this->error = '';
            $this->error_code = '';
            $json = $fetch_result['data'];
            $result = json_decode($json, true);
        } else {
            $this->error = $fetch_result['data'];
            $this->error_code = $fetch_result['code'];
        }

        return $result;
    }

    function get_all_categories() {

        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . '/' . $this->store_id . '/categories';
        $categories = $this->process_request($api_url);

        return $categories;
    }

    function get_subcategories_by_id($parent_category_id = 0) {

        $parent_category_id = intval($parent_category_id);
        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . '/' . $this->store_id . '/categories?parent=' . $parent_category_id;
        $categories = $this->process_request($api_url);

        return $categories;
    }

    function get_all_products() {

        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . '/' . $this->store_id . '/products';
        $products = $this->process_request($api_url);

        return $products;
    }


    function get_products_by_category_id($category_id = 0) {

        $category_id = intval($category_id);
        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id . "/products?category=" . $category_id;
        $products = $this->process_request($api_url);

        return $products;
    }

    function get_product($product_id) {

        static $cached;

        $product_id = intval($product_id);

        if (isset($cached[$product_id])) {
            return $cached[$product_id];
        }

        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id . "/product?id=" . $product_id;
        $cached[$product_id] = $this->process_request($api_url);

        return $cached[$product_id];
    }

    function get_category($category_id) {

        static $cached = array();

        $category_id = intval($category_id);

        if (isset($cached[$category_id])) {
            return $cached[$category_id];
        }
        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id . "/category?id=" . $category_id;
        $cached[$category_id] = $this->process_request($api_url);

        return $cached[$category_id];
    }

    function get_batch_request($params) {

        if (!is_array($params)) {
            return false;
        }

        $api_url = '';
        foreach ($params as $param) {

            $alias = $param["alias"];
            $action = $param["action"];

            if (isset($param['params']))
                $action_params = $param["params"];

            if (!empty($api_url))
                $api_url .= "&";

            $api_url .= ($alias . "=" . $action);

            // if there are the parameters - add it to url
            if (is_array($action_params)) {

                $action_param_str = "?";
                $is_first = true;

                foreach ($action_params as $action_param_name => $action_param_value) {
                    if (!$is_first) {
                        $action_param_str .= "&";
                    }
                    $action_param_str .= $action_param_name . "=" . $action_param_value;
                    $is_first = false;
                }

                $action_param_str = urlencode($action_param_str);
                $api_url .= $action_param_str;
            }

        }

        $api_url =  $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id . "/batch?". $api_url;
        $data = $this->process_request($api_url);

        return $data;
    }

    function get_random_products($count) {

        $count = intval($count);
        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id . "/random_products?count=" . $count;
        $random_products = $this->process_request($api_url);

        return $random_products;
    }

    function get_profile() {

        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id . "/profile";
        $profile = $this->process_request($api_url);

        return $profile;
    }

    function is_api_enabled() {

        // quick and lightweight request
        $api_url = $this->ECWID_PRODUCT_API_ENDPOINT . "/" . $this->store_id . "/profile";

        $this->process_request($api_url);

        return $this->error_code === '';
    }

    function get_method_response_stream($method)
    {

        $request_url = '';
        switch($method) {

            case 'products':
            case 'categories':
                $request_url = $this->ECWID_PRODUCT_API_ENDPOINT . '/' . $this->store_id . '/' . $method;
                break;
            default:
                return false;
        }

        $stream = null;

        try {

            if (ini_get('allow_url_fopen')) {
                $stream = fopen($request_url, 'r');
            } else {
                $response = EcwidPlatform::fetch_url($request_url);
                $body = $response['data'];
                $stream = fopen('php://temp', 'rw');
                fwrite($stream, $body);
                rewind($stream);
            }

        } catch (Exception $e) {

            $stream = null;
        }

        return $stream;
    }
}
