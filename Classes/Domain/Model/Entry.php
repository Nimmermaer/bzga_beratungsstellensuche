<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Model;

use Bzga\BzgaBeratungsstellensuche\Domain\Model\ValueObject\ImageLink;
use SJBR\StaticInfoTables\Domain\Model\CountryZone;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\Annotation\ORM\Lazy;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * @author Sebastian Schreiber
 */
class Entry extends AbstractEntity implements GeopositionInterface, MapWindowInterface
{
    use GeopositionTrait;

    /**
     * @var string
     */
    protected $subtitle = '';

    /**
     * PLZ der Beratungsstelle.
     *
     * @var string
     */
    protected $zip = '';

    /**
     * Ort der Beratungsstelle.
     *
     * @var string
     */
    protected $city = '';

    /**
     * Straße der Beratungsstelle.
     *
     * @var string
     */
    protected $street = '';

    /**
     * Bundesland der Beratungsstelle.
     *
     * @var CountryZone
     */
    protected $state;

    /**
     * Suchinhalt.
     *
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $contactPerson = '';

    /**
     * @var string
     */
    protected $telephone = '';

    /**
     * @var string
     */
    protected $telefax = '';

    /**
     * @var string
     */
    protected $email = '';

    /**
     * @var string
     */
    protected $hotline = '';

    /**
     * @var string
     */
    protected $notice = '';

    /**
     * @var string
     */
    protected $website = '';

    /**
     * @var FileReference
     */
    #[Cascade(['value' => 'remove'])]
    protected $image;

    /**
     * @var ObjectStorage<Category>
     */
    #[Lazy]
    #[Cascade(['value' => 'remove'])]
    protected $categories;

    /**
     * @var string
     */
    protected $institution = '';

    /**
     * @var string
     */
    protected $association = '';

    public function __construct(string $title = '')
    {
        parent::__construct($title);
        $this->categories = new ObjectStorage();
    }

    public function getCategories(): ObjectStorage
    {
        return $this->categories;
    }

    public function setCategories(ObjectStorage $categories): void
    {
        $this->categories = $categories;
    }

    public function attachCategory(Category $category): void
    {
        $this->categories->attach($category);
    }

    public function detachCategory(Category $category): void
    {
        $this->categories->detach($category);
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getState(): ?CountryZone
    {
        return $this->state;
    }

    public function setState(CountryZone $state): void
    {
        $this->state = $state;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getAddress(): string
    {
        $address = '';
        if ($this->zip) {
            $address .= $this->zip;
        }
        if ($this->city) {
            $address .= ' ' . $this->city;
        }
        if ($this->street) {
            $address .= ', ' . $this->street;
        }

        return $address;
    }

    public function getContactPerson(): string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(string $contactPerson): void
    {
        $this->contactPerson = $contactPerson;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function getTelefax(): string
    {
        return $this->telefax;
    }

    public function setTelefax(string $telefax): void
    {
        $this->telefax = $telefax;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getHotline(): string
    {
        return $this->hotline;
    }

    public function setHotline(string $hotline): void
    {
        $this->hotline = $hotline;
    }

    public function getNotice(): string
    {
        return $this->notice;
    }

    public function setNotice(string $notice): void
    {
        $this->notice = $notice;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function setWebsite(string $website): void
    {
        $this->website = $website;
    }

    /**
     * @return FileReference|ImageLink
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param FileReference|ImageLink $image
     */
    public function setImage($image): void
    {
        $this->image = $image;
    }

    public function getInstitution(): string
    {
        return $this->institution;
    }

    public function setInstitution(string $institution): void
    {
        $this->institution = $institution;
    }

    public function getAssociation(): string
    {
        return $this->association;
    }

    public function setAssociation(string $association): void
    {
        $this->association = $association;
    }

    public function getInfoWindow(
        array $parameters = [],
        string $template = '<p><strong>%1$s</strong><br>%2$s<br>%3$s %4$s</p>'
    ): string {
        $title = isset($parameters['detailLink']) ? sprintf(
            '<a href="%2$s">%1$s</a>',
            $this->getTitle(),
            $parameters['detailLink']
        ) : $this->getTitle();

        return sprintf(
            $template,
            $title,
            $this->getStreet(),
            $this->getZip(),
            $this->getCity()
        );
    }

    public function getMapId(): string
    {
       return sprintf('map_%s', StringUtility::getUniqueId());
    }
}
