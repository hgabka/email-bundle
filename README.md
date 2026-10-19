# hgabka/email-bundle

Email kezelő bundle Symfonyhoz és Sonata Adminhoz. Két fő funkciója van:

1. **Email sablonok (template type)** – tranzakciós levelek (kapcsolatfelvétel, jelszó-emlékeztető, aktiváció, …), amelyek váza PHP osztály, a tárgya/tartalma/címzettjei viszont nyelvenként szerkeszthetők az adminban.
2. **Üzenetek (hírlevelek) és feliratkozók** – listákra szervezett feliratkozók, kampányszerű kiküldés sorban (queue), leiratkozó linkekkel.

Mellette: közös layout, queue + cron alapú kiküldés, naplózás, fejlesztői átirányítás (redirect), bounce-ellenőrzés.

## Telepítés

```bash
composer require hgabka/email-bundle
```

```php
// config/bundles.php
Hgabka\EmailBundle\HgabkaEmailBundle::class => ['all' => true],
```

```yaml
# config/routes.yaml
hgabka_email:
    resource: '@HgabkaEmailBundle/Resources/config/routing.yaml'   # /messages/… (webverzió, leiratkozás)
```

Függőségek: `hgabka/utils-bundle`, `hgabka/media-bundle` (csatolmányok, képek), `a2lix/translation-form-bundle`, `twig/cssinliner-extra`, Symfony Mailer. A táblák `hg_email_*` előtaggal jönnek létre (migrációval): `email_template`, `email_layout`, `message`, `message_list`, `message_subscriber`, `message_queue`, `email_queue`, `email_log`, `email_campaign`, `attachment`, stb.

## Konfiguráció (`hgabka_email`)

```yaml
hgabka_email:
    default_sender:    { email: info@example.com, name: Acme }
    default_recipient: { email: office@example.com, name: Office }
    default_name: email_default_name
    editor_role: ROLE_EMAIL_ADMIN        # az admin szerkesztéséhez szükséges szerep

    # funkciók ki/bekapcsolása
    email_templates_enabled: true
    messages_enabled: true
    subscribers_enabled: true
    subscription_enabled: true
    editable_lists: false                # a feliratkozó választhat listát
    editable_layouts: false
    subscriptions: { use_names: true }

    # sablonok
    force_queueing: false                # minden sablonlevél a queue-n át menjen
    template_var_chars: { prefix: '%%', postfix: '%%' }    # vagy egy string: ugyanaz előtag/utótag
    template_var_reader_type: attribute  # attribute | annotation
    auto_create_text_parts: true
    excluded_recipient_classes: { message: [], email_template: [] }
    layout_file: '%kernel.project_dir%/var/layout/%locale%/email_layout.html'

    # üzenetek
    message_with_cc: false
    message_with_bcc: false
    message_return_path: ~
    email_return_path: ~
    return_path: bounce@example.com
    auto_append_unsubscribe_link: false
    message_extra_parameters: {}
    pre_defined_message_recipients: ~
    add_headers:                         # extra fejlécek
        X-Mailer: { type: text, value: hgabka }
    add_recipients: { cc: [], bcc: [] }

    # kiküldés
    send_limit: 50                       # egy cron futásban kiküldött levelek száma
    max_retries: 20
    delete_sent_messages_after: 2        # nap

    # naplózás
    message_logging: true
    log_path: '%kernel.logs_dir%/message'
    use_email_logging: true

    # fejlesztői átirányítás: a levelek ide menjenek a valódi címzettek helyett
    redirect:
        enable: true
        recipients: [dev@example.com]
        hosts: [localhost]               # csak ezeken a hosztokon aktív
        subject_append: true

    bounce_checking:
        after_process: delete            # delete | leave_as_is | mark_as_read
        account: { host: ~, port: ~, type: ~, address: ~, user: ~, pass: ~ }
```

## Sablonlevelek

### Template type

```php
use Hgabka\EmailBundle\Annotation\TemplateVar;
use Hgabka\EmailBundle\Model\AbstractEmailTemplateType;

class ContactEmailTemplateType extends AbstractEmailTemplateType
{
    protected array $formData = [];

    public function setFormData(array $formData): self { $this->formData = $formData; return $this; }

    #[TemplateVar(label: 'Név', placeholder: 'nev')]
    public function getName(): string { return $this->formData['name'] ?? ''; }
}
```

Az `EmailTemplateTypeInterface`-t megvalósító osztályok automatikusan a `hg_email.email_template_type` taget kapják. Az osztály beállíthatja az alap tárgyat/tartalmat (`$defaultSubject`, `$defaultHtmlContent`, `$defaultTextContent`), feladót (`$defaultFromName/Email`), alap címzetteket (`getDefaultRecipients()`), és hogy mi szerkeszthető az adminban (`isToEditable()`, `isCcEditable()`, `isBccEditable()`, `isSenderEditable()`). A `#[TemplateVar]`-ral jelölt getterek a szerkesztőben beszúrható változók (`%%nev%%`); az `alterEmail(Email $email)` az utolsó pillanatban módosíthatja a levelet.

Ha a típushoz még nincs `EmailTemplate` rekord, a `Helper\TemplateTypeManager` létrehozza (nyelvenként `EmailTemplateTranslation`: tárgy, szöveg, HTML), amit az `EmailTemplateAdmin` szerkeszt.

### Küldés

```php
use Hgabka\EmailBundle\Helper\MessageSender;

$messageSender->sendTemplateMail(ContactEmailTemplateType::class, [
    'formData' => $data,           // a type setter-einek paraméterei (setFormData)
]);                                 // sendTemplateMail($class, $params = [], $sendParams = [], $locale = null)

// sorba állítás (opcionális időzítéssel):
$messageSender->enqueueTemplateMessage(ContactEmailTemplateType::class, ['formData' => $data], [], null, new \DateTime('+1 hour'));
```

`sendTemplateMail()` azonnal küld (vagy `force_queueing` esetén sorba tesz), `enqueueTemplateMessage()` mindig a `email_queue`-ba. A levél összeállítását a `Helper\MailBuilder::createTemplateMessage()` végzi, amit a következő események vesznek körül (`Event\MailBuilderEvents`): `SET_DEFAULT_SENDER`, `SET_DEFAULT_RECIPIENT` (alap feladó/címzett felülírása), `BuildTemplateMailEvent`, `BuildMessageMailEvent`; a küldésnél `MailSenderEvents`, `MailerEvent`, `MailExceptionEvent`, `MailRecipientEvent`.

### Címzett típusok

A `Model\RecipientTypeInterface` implementációi (`Recipient\*`: `GeneralRecipientType`, `DefaultRecipientType`, feliratkozók stb.) a sablonhoz/üzenethez rendelhető címzett-forrásokat adják (`hg_email.email_template_recipient_type`, `hg_email.message_recipient_type` tagek). Sajátot a `AbstractRecipientType` / `AbstractEmailTemplateRecipientType` / `AbstractMessageRecipientType` ősből érdemes származtatni (`getRecipients()`, `addFormFields()`, `getParams()`, …).

## Layout

Az `EmailLayout` (nyelvenként szerkeszthető, `editable_layouts`) vagy a `layout_file` adja a levelek keretét. A layout változói a `Model\LayoutVarInterface` implementációi (`hg_email.layout_var` tag); beépítettek: `ContentLayoutVar`, `SubjectLayoutVar`, `SenderNameLayoutVar`, `SenderEmailLayoutVar`, `RecipientNameLayoutVar`, `RecipientEmailLayoutVar`. A CSS beégetése (inline) a `twig/cssinliner-extra`-val történik.

## Üzenetek és feliratkozók

- `Message` (+ `MessageTranslation`) – hírlevél: tárgy, tartalom, feladó, csatolmányok, státusz (`Enum\MessageStatusEnum`), `MessageSendList` – kiküldési lista.
- `MessageList` – feliratkozási listák; `MessageSubscriber` – feliratkozók (`MessageListSubscription` a kapcsolat).
- `Helper\SubscriptionManager` – `createSubscription($email, $name, $locale, $lists)`, `deleteSubscription()`, `addSubscriberToLists()`, `generateSubscriberToken()`, `getListChoices()`, …
- Üzenet-változók (`Model\MessageVarInterface`, tag: `hg_email.message_var`): beépítettek az `UnsubscribeLinkMessageVar`, `UnsubscribeUrlMessageVar`, `WebversionMessageVar`.
- `Controller\MessageController` – `/messages/…`: webverzió, leiratkozás.
- Adminok: `EmailTemplateAdmin`, `EmailLayoutAdmin`, `MessageAdmin`, `MessageListAdmin`, `SubscriberAdmin`; `EmailVoter` a hozzáféréshez.

## Queue és cron

A kiküldést két parancs végzi, ezeket crontabból futtasd:

| Parancs | Leírás |
|---|---|
| `hgabka:email:send-emails` | az `email_queue` (sablonlevelek) kiküldése |
| `hgabka:email:send-messages` | a `message_queue` (hírlevelek) kiküldése |

Futásonként legfeljebb `send_limit` levél megy ki; hibás küldést `max_retries`-ig próbál újra; a kiküldött üzeneteket `delete_sent_messages_after` nap után törli. (`Helper\QueueManager`, `Helper\MessageSender`, `Entity\EmailQueue/MessageQueue`, `Enum\QueueStatusEnum`.)

Naplózás: `Logger\EmailLogger` (adatbázis, `EmailLog`) és `Logger\MessageLogger` (fájl, `log_path`). A bounce-olt levelek feldolgozását a `Helper\BounceChecker` + `MailboxReader` végzi a `bounce_checking.account` postaládából.

## Twig

Az `EmailTwigExtension` az admin űrlapokhoz ad függvényeket: `render_template_usable_vars`, `render_message_usable_vars`, `render_layout_usable_vars` (beszúrható változók listája), `render_template_recipient_selector`, `render_message_recipient_selector`, `render_subscriber_lists`.
