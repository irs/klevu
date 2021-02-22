<?php

namespace Irs\Klevu\Model;

class ProductInfo
{
    private $id;
    private $name;
    private $price;
    private $specialPrice;
    private $images = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getSpecialPrice(): ?float
    {
        return $this->specialPrice;
    }

    public function setSpecialPrice(?float $specialPrice): self
    {
        $this->specialPrice = $specialPrice;

        return $this;
    }

    /**
     * Returns product images URLs
     *
     * @return string[]
     */
    public function getImages(): array
    {
        return $this->images;
    }

    /**
     * Sets product images URLs
     *
     * @param string[] $images
     */
    public function setImages(array $images): self
    {
        $this->images = $images;

        return $this;
    }
}
