<?php

namespace Hgabka\EmailBundle\Event;

class MailBuilderEvents
{
    public const SET_DEFAULT_SENDER = 'hg_email.builder.set_default_sender';
    public const SET_DEFAULT_RECIPIENT = 'hg_email.builder.set_default_recipient';
    public const BUILD_MESSAGE_MAIL = 'hg_email.builder.build_message_mail';
    public const BUILD_TEMPLATE_MAIL = 'hg_email.builder.build_template_mail';
}
