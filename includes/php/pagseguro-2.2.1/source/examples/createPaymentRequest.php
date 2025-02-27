<?php //

/*
 * ***********************************************************************
 Copyright [2011] [PagSeguro Internet Ltda.]

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

 http://www.apache.org/licenses/LICENSE-2.0

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 * ***********************************************************************
 */

require_once "../PagSeguroLibrary/PagSeguroLibrary.php";

/**
 * Class with a main method to illustrate the usage of the domain class PagSeguroPaymentRequest
 */
class CreatePaymentRequest
{

    public static function main()
    {
        // Instantiate a new payment request
        $paymentRequest = new PagSeguroPaymentRequest();

        // Sets the currency
        $paymentRequest->setCurrency("BRL");

        // Add an item for this payment request
        $paymentRequest->addItem('0001', 'Notebook prata', 2, 430.00);

        // Add another item for this payment request
        $paymentRequest->addItem('0002', 'Notebook rosa', 2, 560.00);

        // Sets a reference code for this payment request, it is useful to identify this payment
        // in future notifications.
        $paymentRequest->setReference("REF123");

        // Sets shipping information for this payment request
        $sedexCode = PagSeguroShippingType::getCodeByType('SEDEX');
        $paymentRequest->setShippingType($sedexCode);
        $paymentRequest->setShippingAddress(
            '01452002',
            'Av. Brig. Faria Lima',
            '1384',
            'apto. 114',
            'Jardim Paulistano',
            'São Paulo',
            'SP',
            'BRA'
        );

        // Sets your customer information.
        $paymentRequest->setSender(
            'João Comprador',
            'comprador@s2it.com.br',
            '11',
            '56273440',
            'CPF',
            '156.009.442-76'
        );

        // Sets the url used by PagSeguro for redirect user after ends checkout process
        $paymentRequest->setRedirectUrl("http://www.lojamodelo.com.br");

        // Add checkout metadata information
        $paymentRequest->addMetadata('PASSENGER_CPF', '15600944276', 1);
        $paymentRequest->addMetadata('GAME_NAME', 'DOTA');
        $paymentRequest->addMetadata('PASSENGER_PASSPORT', '23456', 1);

        // Another way to set checkout parameters
        $paymentRequest->addParameter('notificationURL', 'http://www.lojamodelo.com.br/nas');
        $paymentRequest->addParameter('senderBornDate', '07/05/1981');
        $paymentRequest->addIndexedParameter('itemId', '0003', 3);
        $paymentRequest->addIndexedParameter('itemDescription', 'Notebook Preto', 3);
        $paymentRequest->addIndexedParameter('itemQuantity', '1', 3);
        $paymentRequest->addIndexedParameter('itemAmount', '200.00', 3);

        try {

            /*
             * #### Credentials #####
             * Substitute the parameters below with your credentials (e-mail and token)
             * You can also get your credentials from a config file. See an example:
             * $credentials = PagSeguroConfig::getAccountCredentials();
             */
            $credentials = new PagSeguroAccountCredentials("vendedor@lojamodelo.com.br",
                "E231B2C9BCC8474DA2E260B6C8CF60D3");
            $credentials = PagSeguroConfig::getAccountCredentials();
            // Register this payment request in PagSeguro, to obtain the payment URL for redirect your customer.
            $url = $paymentRequest->register($credentials);

            self::printPaymentUrl($url);
        } catch (PagSeguroServiceException $e) {
            die($e->getMessage());
        }
    }

    public static function printPaymentUrl($url)
    {
        if ($url) {
            echo "<h2>Criando requisi&ccedil;&atilde;o de pagamento</h2>";
            echo "<p>URL do pagamento: <strong>$url</strong></p>";
            echo "<p><a title=\"URL do pagamento\" href=\"$url\">Ir para URL do pagamento.</a></p>";
        }
    }
}

CreatePaymentRequest::main();
