<?php

namespace Deploymeta\WhatsAppNotifier\Messages;

class WhatsAppMessage
{
    public function __construct(
        public string $type = 'text',
        public ?string $text = null,
        public ?string $templateName = null,
        public string $templateLanguage = 'en_US',
        public array $templateComponents = [],
        public array $meta = [],
    ) {}

    public static function text(string $text, array $meta = []): self
    {
        return new self(type: 'text', text: $text, meta: $meta);
    }

    public static function template(string $templateName, string $templateLanguage = 'en_US', array $templateComponents = [], array $meta = []): self
    {
        return new self(
            type: 'template',
            templateName: $templateName,
            templateLanguage: $templateLanguage,
            templateComponents: $templateComponents,
            meta: $meta,
        );
    }
}
