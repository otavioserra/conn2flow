<?php
/**
 * Observador de Notifica��es de Pagamento Instant�neo
 */
class InstantPaymentNotification
{
    /**
     * @var string
     */
    private $endpoint = 'https://www.paypal.com';
 
    /**
     * @var IPNHandler
     */
    private $ipnHandler;
 
    /**
     * Constroi o objeto que receber� as notifica��es de pagamento
     * instant�neas do PayPal..
     * @param   boolean $sandbox Define se ser� utilizado o Sandbox
     * @throws  InvalidArgumentException
     */
    public function __construct($sandbox = false)
    {
        if (!!$sandbox) {
            $this->endpoint = 'https://www.sandbox.paypal.com';
        }
 
        $this->endpoint .= '/cgi-bin/webscr?cmd=_notify-validate';
    }
 
    /**
     * Aguarda por notifica��es de pagamento instant�nea; Caso uma nova
     * notifica��o seja recebida, faz a verifica��o e notifica um manipulador
     * com o status (verificada ou n�o) e a mensagem recebida.
     * @see     InstantPaymentNotification::setIPNHandler()
     * @throws  BadMethodCallException Caso o m�todo seja chamado antes
     * de um manipulador ter sido definido.
     */
    public function listen()
    {
        if ($this->ipnHandler !== null) {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                if (filter_input(INPUT_POST, 'receiver_email', FILTER_VALIDATE_EMAIL)) {
                    $curl = curl_init();
 
                    curl_setopt($curl, CURLOPT_URL, $this->endpoint);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
 
                    $response = curl_exec($curl);
                    $error = curl_error($curl);
                    $errno = curl_errno($curl);
 
                    curl_close($curl);
 
                    if (empty($error) && $errno == 0) {
                        $this->ipnHandler->handle($response == 'VERIFIED', $_POST);
                    }
                }
            }
        } else {
            throw new BadMethodCallException('Nenhum manipulador de mensagem ou email foi definido');
        }
    }
 
    /**
     * Define o objeto que ir� manipular as notifica��es de pagamento
     * instant�neas enviadas pelo PayPal.
     * @param   IPNHandler $ipnHandler
     */
    public function setIPNHandler(IPNHandler $ipnHandler)
    {
        $this->ipnHandler = $ipnHandler;
    }
}

/**
 * Interface para defini��o de um manipulador de notifica��o
 * de pagamento instant�nea.
 */
interface IPNHandler
{
    /**
     * Manipula uma notifica��o de pagamento instant�nea recebida
     * pelo PayPal.
     * @param   boolean $isVerified Identifica que a mensagem foi
     * verificada como tendo sido enviada pelo PayPal.
     * @param   array $message Mensagem completa enviada pelo
     * PayPal.
     */
    public function handle($isVerified, array $message);
}
?>