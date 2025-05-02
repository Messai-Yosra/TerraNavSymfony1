<?php
// filepath: src/Service/utilisateurs/TwilioSMSService.php
namespace App\Service\utilisateurs;

use Twilio\Rest\Client;

class TwilioSMSService
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private ?Client $client = null;

    public function __construct(string $accountSid, string $authToken, string $fromNumber)
    {
        $this->accountSid = $accountSid ;
        $this->authToken = $authToken ;
        $this->fromNumber = $fromNumber ;
    }

    private function initClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client($this->accountSid, $this->authToken);
        }
        
        return $this->client;
    }

    /**
     * Envoie un SMS via Twilio
     * 
     * @param string $toNumber Le numéro du destinataire (format E.164, ex: +21699123456)
     * @param string $messageBody Le contenu du SMS
     * @return string|null L'ID du message si réussi, null si échec
     */
    public function sendSMS(string $toNumber, string $messageBody): ?string
    {
        try {
            $client = $this->initClient();
            
            $message = $client->messages->create(
                $toNumber,
                [
                    'from' => $this->fromNumber,
                    'body' => $messageBody
                ]
            );
            
            return $message->sid;
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('Échec d\'envoi SMS: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Envoie une notification personnalisée pour une réclamation traitée
     * 
     * @param string $phoneNumber Le numéro de téléphone du destinataire
     * @param string $email L'email de l'utilisateur pour personnaliser le message
     * @return string|null L'identifiant du message ou null si échec
     */
    public function sendReclamationProcessedNotification(string $phoneNumber, string $email): ?string
    {
        $message = sprintf(
            "Bonjour, votre réclamation a été traitée avec succès. Pour plus d'informations, vous pouvez consulter votre compte sur l'adresse %s. Merci de votre confiance, l'équipe TerraNav",
            $email
        );
        
        return $this->sendSMS($phoneNumber, $message);
    }
}