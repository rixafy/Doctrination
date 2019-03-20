<?php

declare(strict_types=1);

namespace Rixafy\Doctrination;

use Rixafy\Doctrination\Language\Exception\LanguageNotFoundException;
use Rixafy\Doctrination\Language\Language;
use Rixafy\Doctrination\Language\LanguageFacade;
use Rixafy\Doctrination\Language\LanguageHolder;

class Doctrination
{
    /** @var LanguageFacade */
    private $languageFacade;

    /** @var Language */
    private $language;

    /**
     * Doctrination constructor.
     * @param LanguageFacade $languageFacade
     */
    private function __construct(LanguageFacade $languageFacade)
    {
        $this->languageFacade = $languageFacade;
    }

    /**
     * @param string $isoCode
     * @throws LanguageNotFoundException
     */
    public function setLanguage(string $isoCode): void
    {
        $this->language = $this->languageFacade->getByIso($isoCode);
        LanguageHolder::setLanguage($this->language);
    }

    /**
     * @return Language
     */
    public function getLanguage(): Language
    {
        return $this->language;
    }
}