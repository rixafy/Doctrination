<?php

declare(strict_types=1);

namespace Rixafy\Doctrination;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use ReflectionClass;
use \Rixafy\Doctrination\Language\Language;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class EntityTranslator
{
    /**
     * Many Stores have One Language.
     * @ORM\ManyToOne(targetEntity="\Rixafy\Doctrination\Language\Language", inversedBy="entity")
     * @var \Rixafy\Doctrination\Language\Language
     */
    protected $fallback_language;

    protected $translation;

    protected $translationLanguage;

    /**
     * @ORM\PostLoad
     * @throws Exception\UnsetLanguageException
     * @throws \ReflectionException
     */
    public function injectTranslation()
    {
        $language = Doctrination::getLanguage();

        if ($this->translation === null) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('language', $language))
                ->setMaxResults(1);

            $this->translation = $this->getTranslations()->matching($criteria)->first();
            $this->translationLanguage = $language;

            if (!$this->translation) {
                $criteria = Criteria::create()
                    ->where(Criteria::expr()->eq('language', $this->fallback_language))
                    ->setMaxResults(1);

                $this->translation = $this->getTranslations()->matching($criteria)->first();
                $this->translationLanguage = $this->fallback_language;
            }
        }

        $this->injectFields();
    }

    /**
     * @throws \ReflectionException
     */
    protected function injectFields()
    {
        $reflection = new ReflectionClass($this->translation);

        foreach($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $this->{$property->getName()} = $property->getValue();
        }
    }

    /**
     * @param \Rixafy\Doctrination\Language\Language $language
     */
    protected function configureFallbackLanguage(Language $language)
    {
        if ($this->fallback_language === null) {
            $this->fallback_language = $language;
        }
    }

    /**
     * @param $dataObject
     * @param Language $language
     * @throws Exception\UnsetLanguageException
     */
    public function editTranslation($dataObject, Language $language)
    {
        if ($this->translation !== null && $language === $this->translationLanguage) {
            try {
                $reflection = new ReflectionClass($this->translation);
                foreach ($reflection->getProperties() as $property) {
                    $camelKey = str_replace('_', '', ucwords($property->getName(), '_'));
                    if (isset($dataObject->{$camelKey})) {
                        $value = $dataObject->{$camelKey};
                        $property->setAccessible(true);
                        $property->setValue($value);
                        $this->{$property->getName()} = $value;
                    }
                }
            } catch (\ReflectionException $e) {
            }
        } else {
            if ($this->fallback_language === null) {
                $this->fallback_language = $language;
                $this->translation = $this->addTranslation($dataObject, $language);
                $this->translationLanguage = $language;
                try {
                    $this->injectFields();
                } catch (\ReflectionException $e) {
                }
            } else {
                $translation = $this->addTranslation($dataObject, $language);
                if ($language === Doctrination::getLanguage()) {
                    $this->translation = $translation;
                    $this->translationLanguage = $language;
                    try {
                        $this->injectFields();
                    } catch (\ReflectionException $e) {
                    }
                }
            }
        }
    }

    public function addTranslation($dataObject, Language $language)
    {
        $translation = new (get_class($this) . 'Translation')($dataObject, $language, $this);

        $this->translations->add($translation);

        if ($this->fallback_language === null) {
            $this->fallback_language = $language;
        }

        return $translation;
    }

    /**
     * @return Selectable
     */
    public abstract function getTranslations();
}