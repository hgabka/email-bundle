<?php

namespace Hgabka\EmailBundle\Event;

class MailBuilderEvents
{
    const SET_DEFAULT_SENDER = 'hg_email.builder.set_default_sender';
    const SET_DEFAULT_RECIPIENT = 'hg_email.builder.set_default_recipient';
    const BUILD_MESSAGE_MAIL = 'hg_email.builder.build_message_mail';
    const BUILD_TEMPLATE_MAIL = 'hg_email.builder.build_template_mail';
}
