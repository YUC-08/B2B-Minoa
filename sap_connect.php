<?php
class SAPConnect {
    private $baseUrl = "https://192.168.54.185:50000/b1s/v2/";
    private $sessionId;

    public function __construct() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['sapSession'])) {
        $this->sessionId = $_SESSION['sapSession'];
    }
}

    public function login($username, $password, $companyDB) {
        $url = $this->baseUrl . "Login";
        $data = [
            "CompanyDB" => $companyDB,
            "UserName" => $username,
            "Password" => $password 
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,  //SAP’ye “bir veri gönderiyorum” anlamına gelir.
            CURLOPT_POSTFIELDS => json_encode($data), //SAP’ye gönderilecek veriyi JSON formatında gönder.
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'], //Bu isteğin içeriği JSON’dur” diye başlığa (Header’a) yaz.
            CURLOPT_SSL_VERIFYPEER => false, //SSL sertifikasını doğrulama.
            CURLOPT_SSL_VERIFYHOST => false //Sunucu adını (hostname) doğrulama.
        ]);

        $response = curl_exec($ch);   //Bu, tüm yukarıdaki ayarlarla birlikte isteği SAP’ye gönderiyor.
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); //SAP yanıt dönerse $response içinde JSON olarak gelir:
        curl_close($ch);

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $this->sessionId = $result["SessionId"];
            $_SESSION["sapSession"] = $this->sessionId;
            return true;
        } else {
            return false;
        }
    } 



    private function sendRequest($method, $endpoint, $payload = null) { //GET, POST, PATCH gibi HTTP metodu
        $url = $this->baseUrl . $endpoint;  //SAP’ye gönderilecek veri (örneğin yeni sipariş oluşturma verisi)
        $ch = curl_init($url);

        $headers = ["Content-Type: application/json"];
        if ($this->sessionId) {
            $headers[] = "Cookie: B1SESSION=" . $this->sessionId; //session token (SessionId) 
                                                                  //Bu cookie olmadan SAP “Invalid session” hatası döner.
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers, 
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        if ($payload) {  //gönderilecek veriyi JSON’a çevirip isteğin gövdesine koyar.
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        /*
        $payload = [
    "CardCode" => "V100",
    "DocDate" => "2025-10-10",
    "Comments" => "Yeni sipariş"    JSON = {"CardCode":"V100","DocDate":"2025-10-10","Comments":"Yeni sipariş"}
    ]; 
        **/

        $response = curl_exec($ch);  //SAP’ye isteği yollar, cevabı JSON olarak alır.
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); //SAP’nin HTTP yanıt kodunu alır. //(örnek: 200 = başarılı, 400 = hatalı istek, 301 = session timeout)
        curl_close($ch); 

        $json = json_decode($response, true); //SAP’den gelen cevap JSON’sa → array’e çevir.
        return is_array($json)
    ? ["status" => $httpCode, "response" => $json]
    : ["status" => $httpCode, "response" => ["raw" => $response]];  //Eğer değilse (örneğin HTML hata mesajı döndüyse) → ham cevabı "raw" olarak sakla.

    } 

    public function get($endpoint) { return $this->sendRequest("GET", $endpoint); }
    public function post($endpoint, $payload) { return $this->sendRequest("POST", $endpoint, $payload); }
    public function patch($endpoint, $payload) { return $this->sendRequest("PATCH", $endpoint, $payload); }
}
?>
