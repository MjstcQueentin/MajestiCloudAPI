<?php
require_once(__DIR__ . "/../GlobalPDO.class.php");

class OAuthPDO extends GlobalPDO
{
    function __construct($env)
    {
        parent::__construct($env);
    }

    public function insert_authorization(
        string $authorization_code,
        string $user_uuid,
        string $client_uuid,
        bool $require_mfa = false,
        string $code_challenge = null,
        string $code_challenge_method = null
    ) {
        $stmt = $this->pdo->prepare("INSERT INTO oauth_authorization(authorization_key, user_uuid, client_uuid, code_challenge, code_challenge_method, require_mfa) VALUES (:authorization_code, :user_uuid, :client_uuid, :code_challenge, :code_challenge_method, :require_mfa)");
        $stmt->bindValue("authorization_code", $authorization_code);
        $stmt->bindValue("user_uuid", $user_uuid);
        $stmt->bindValue("client_uuid", $client_uuid);
        $stmt->bindValue("code_challenge", $code_challenge);
        $stmt->bindValue("code_challenge_method", $code_challenge_method);
        $stmt->bindValue("require_mfa", $require_mfa ? 1 : 0);
        return $stmt->execute();
    }

    public function select_authorization(string $code, string $client_uuid)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM oauth_authorization WHERE authorization_key = :code AND client_uuid = :client_uuid");
        $stmt->bindValue("code", $code);
        $stmt->bindValue("client_uuid", $client_uuid);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function set_authorization_mfa_requirement(string $authorization_code,  bool $require_mfa)
    {
        $stmt = $this->pdo->prepare("UPDATE oauth_authorization SET require_mfa = :require_mfa WHERE authorization_key = :code");
        $stmt->bindValue("code", $authorization_code);
        $stmt->bindValue("require_mfa", $require_mfa ? 1 : 0);
        return $stmt->execute();
    }

    public function delete_authorization($client_uuid, $user_uuid)
    {
        $stmt = $this->pdo->prepare("DELETE FROM oauth_authorization WHERE user_uuid = :user_uuid AND client_uuid = :client_uuid");
        $stmt->bindValue("user_uuid", $user_uuid);
        $stmt->bindValue("client_uuid", $client_uuid);
        return $stmt->execute();
    }
}
