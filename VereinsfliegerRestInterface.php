<?php
class VereinsfliegerRestInterface
{
    private $config;
    private $AccessToken;
    private $HttpStatusCode = 0;
    private $aResponse = array();
    private $InterfaceUrl;

    public function __construct()
    {
        $this->config = include 'config.php';
        $this->InterfaceUrl = $this->config['api_url'];
    }

    public function SignIn($UserName, $Password)
    {
        $this->SendRequest("GET", "auth/accesstoken", null);
        if ($this->HttpStatusCode != 200 || !isset($this->aResponse['accesstoken'])) {
            return false;
        }
        $this->AccessToken = $this->aResponse['accesstoken'];

        $PassWordHash = md5($Password);

        $Data = array(
            'accesstoken' => $this->AccessToken,
            'username' => $UserName,
            'password' => $PassWordHash,
            'cid' => $this->config['cid'],
            'appkey' => $this->config['app_key'],
            'auth_secret' => $this->config['auth_secret']
        );
        $this->SendRequest("POST", "auth/signin", $Data);
        return ($this->HttpStatusCode == 200);
    }

    public function GetUser()
    {
        $Data = array('accesstoken' => $this->AccessToken);
        $this->SendRequest("POST", "auth/getuser", $Data);
        if ($this->HttpStatusCode == 200) {
            return $this->aResponse;
        }
        return array();
    }

    public function SignOut()
    {
        if (empty($this->AccessToken)) {
            return false;
        }
        $this->SendRequest("DELETE", "auth/signout/" . $this->AccessToken, null);
        return ($this->HttpStatusCode == 200);
    }

    /**
     * Erstellt einen neuen Termin im Vereinsflieger-Kalender.
     *
     * @param string $title Der Titel des Termins.
     * @param string $dateFrom Startdatum und -zeit (Format: Y-m-d H:i).
     * @param string $dateTo Enddatum und -zeit (Format: Y-m-d H:i).
     * @param string $comment Ein optionaler Kommentar/Beschreibung.
     * @param string $location Ein optionaler Ort.
     * @return bool True bei Erfolg, false bei einem Fehler.
     */
    public function createCalendarAppointment($title, $dateFrom, $dateTo, $comment = '', $location = '')
    {
        if (empty($this->AccessToken)) {
            return false;
        }

        $Data = array(
            'accesstoken' => $this->AccessToken,
            'title'       => $title,
            'datefrom'    => $dateFrom,
            'dateto'      => $dateTo,
            'comment'     => $comment,
            'location'    => $location
        );

        $this->SendRequest("POST", "calendar/add", $Data);
        
        return ($this->HttpStatusCode == 200);
    }

    private function SendRequest($Method, $Resource, $Data)
    {
        $InterfaceUrl = $this->InterfaceUrl . $Resource;
        $CurlHandle = curl_init();
        curl_setopt($CurlHandle, CURLOPT_URL, $InterfaceUrl);
        switch ($Method) {
            case 'GET':
            case 'POST':
                $Fields = http_build_query(is_array($Data) ? $Data : array(), '', '&');
                curl_setopt($CurlHandle, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($Fields)));
                curl_setopt($CurlHandle, CURLOPT_POST, 1);
                curl_setopt($CurlHandle, CURLOPT_POSTFIELDS, $Fields);
                break;
            case 'PUT':
                $Fields = http_build_query(is_array($Data) ? $Data : array(), '', '&');
                curl_setopt($CurlHandle, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($Fields)));
                curl_setopt($CurlHandle, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($CurlHandle, CURLOPT_POSTFIELDS, $Fields);
                break;
            case 'DELETE':
                $Fields = http_build_query(is_array($Data) ? $Data : array(), '', '&');
                curl_setopt($CurlHandle, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($Fields)));
                curl_setopt($CurlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($CurlHandle, CURLOPT_POSTFIELDS, $Fields);
                break;
        }
        curl_setopt($CurlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($CurlHandle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($CurlHandle, CURLOPT_SSL_VERIFYPEER, false);
        $Html = curl_exec($CurlHandle);
        $this->HttpStatusCode = curl_getinfo($CurlHandle, CURLINFO_HTTP_CODE);
        $ContentType = curl_getinfo($CurlHandle, CURLINFO_CONTENT_TYPE);
        if ($ContentType == 'application/zip') {
            $this->aResponse = $Html;
            curl_close($CurlHandle);
            return true;
        }
        curl_close($CurlHandle);
        $this->aResponse = json_decode($Html, true);
        return !empty($this->aResponse);
    }
}
?>