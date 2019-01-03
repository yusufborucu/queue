<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class SendMernis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $informations = array(
            "name" => $this->user['name'],
            "surname" => $this->user['surname'],
            "birth_year" => $this->user['birth_year'],
            "nationality_id" => $this->user['nationality_id']
        );
        $response = $this->mernis_control($informations);

        $content = $response == "true" ? 'Bilgileriniz doğru.' : 'Bilgileriniz yanlış.';
        $data = array(
            'content' => $content
        );
        $this->send_mail('email.mernis_control', $data, $this->user['email'], 'Mernis Kontrolü');
    }

    public function mernis_control($informations)
    {
        $send = '<?xml version="1.0" encoding="utf-8"?>
		            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                        <soap:Body>
                            <TCKimlikNoDogrula xmlns="http://tckimlik.nvi.gov.tr/WS">
                                <TCKimlikNo>'.$informations["nationality_id"].'</TCKimlikNo>
                                <Ad>'.$informations["name"].'</Ad>
                                <Soyad>'.$informations["surname"].'</Soyad>
                                <DogumYili>'.$informations["birth_year"].'</DogumYili>
                            </TCKimlikNoDogrula>
                        </soap:Body>
		            </soap:Envelope>';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,            "https://tckimlik.nvi.gov.tr/Service/KPSPublic.asmx");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST,           true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER,         false);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     $send);
        curl_setopt($ch, CURLOPT_HTTPHEADER,     array(
            'POST /Service/KPSPublic.asmx HTTP/1.1',
            'Host: tckimlik.nvi.gov.tr',
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "http://tckimlik.nvi.gov.tr/WS/TCKimlikNoDogrula"',
            'Content-Length: '.strlen($send)
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        return strip_tags($response);
    }

    public function send_mail($template, $data, $to, $subject)
    {
        Mail::send($template, $data, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }
}
