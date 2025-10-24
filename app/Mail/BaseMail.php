<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use MailerSend\LaravelDriver\MailerSendTrait;

abstract class BaseMail extends Mailable
{
    use MailerSendTrait, Queueable, SerializesModels;

    public array $model;

    public function __construct(array $model)
    {
        $this->model = $model;
    }
}
