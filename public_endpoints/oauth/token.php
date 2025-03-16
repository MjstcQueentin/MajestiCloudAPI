<?php

require_once(__DIR__ . "/../../engine/oauth/OAuthEngine.class.php");
$engine = new OAuthEngine();

// Check the method
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $engine->echo_response([
        "status" => false,
        "message" => "This endpoint requires POST."
    ], 405);
}

// Check if all mendatory parameters are supplied
if (empty($_POST["authorization_code"]) || empty($_POST["client_uuid"])) {
    $engine->echo_response([
        "status" => false,
        "message" => "Missing parameters."
    ], 400);
}

// Check if the authorization exists
$authorization = $engine->get_authorization(trim($_POST["authorization_code"]), trim($_POST["client_uuid"]));
if ($authorization === false) {
    $engine->echo_response([
        "status" => false,
        "message" => "Could not recognize the authorization code."
    ], 401);
}

// Check if MFA is required
if ($authorization["require_mfa"] == 1) {
    $engine->echo_response([
        "status" => false,
        "message" => "The user must go through the Multi-Factor Authentication step before you can use this code."
    ], 401);
}

if (!empty($authorization["code_challenge"])) {
    if (empty($_POST["code_verifier"])) {
        $engine->echo_response([
            "status" => false,
            "message" => "You must supply a PKCE Code Verifier as the authorization was created using this method."
        ], 400);
    }

    $pkce_valid = false;
    switch ($authorization["code_challenge_method"]) {
        case "plain":
        default:
            $pkce_valid = $authorization["code_challenge"] == trim($_POST["code_verifier"]);
            break;
        case "S256":
            $s256 = hash("sha256", $_POST["code_verifier"], true);
            $s256 = sodium_bin2base64($s256, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
            $pkce_valid = hash_equals($authorization["code_challenge"], $s256);
            break;
    }

    if (!$pkce_valid) {
        $engine->echo_response([
            "status" => false,
            "message" => "The supplied PKCE Code Verifier does not match the authorization's code challenge. $s256"
        ], 401);
    }
} else {
    if (empty($_POST["client_secret"])) {
        $engine->echo_response([
            "status" => false,
            "message" => "You must supply a Client Secret as the authorization was created without a PKCE Code Challenge."
        ], 400);
    }

    $client = $engine->select_client(trim($_POST["client_uuid"]));
    if ($client["secret_key"] != trim($_POST["client_secret"])) {
        $engine->echo_response([
            "status" => false,
            "message" => "Could not recognize the client secret key."
        ], 403);
    }
}

$token = $engine->create_session($authorization['authorization_key'], trim($_POST["client_uuid"]));

$engine->clear_authorizations(trim($_POST["client_uuid"]), $authorization["user_uuid"]);

$engine->echo_response([
    "status" => true,
    "message" => "Successfully authenticated.",
    "access_token" => $token
], 200);
