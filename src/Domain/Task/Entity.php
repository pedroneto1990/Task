<?php
namespace Domain\Task;

class Entity
{
    /** @var int */
    private $id;

    /** @var string */
    private $uuid;

    /** @var string */
    private $type;

    /** @var string */
    private $content;

    /** @var int */
    private $sortOrder;

    /** @var bool */
    private $done;

    /** @var string */
    private $dateCreated;

    /**
     * @return int
     */
    public function getId() :? int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) : void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getUuid() :? string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid) : void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getType() :? string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getContent() :? string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return int
     */
    public function getSortOrder() :? int
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     */
    public function setSortOrder(int $sortOrder) : void
    {
        $this->sortOrder = $sortOrder;
    }

    /**
     * @return bool
     */
    public function isDone() :? bool
    {
        return $this->done;
    }

    /**
     * @param bool $done
     */
    public function setDone(bool $done) : void
    {
        $this->done = $done;
    }

    /**
     * @return string
     */
    public function getDateCreated() :? string
    {
        return $this->dateCreated;
    }

    /**
     * @param string $dateCreated
     */
    public function setDateCreated($dateCreated) : void
    {
        if ($dateCreated instanceof \DateTime) {
            $dateCreated = $dateCreated->format('Y-m-d H:i:s');
        }

        $this->dateCreated = $dateCreated;
    }

    /**
     * Transform object to array
     *
     * @return array
     */
    public function toArray() : array
    {
        return $this->removeNullValues([
            'id_task' => $this->getId(),
            'uuid' => $this->getUuid(),
            'type' => $this->getType(),
            'content' => $this->getContent(),
            'sort_order' => $this->getSortOrder(),
            'done' => $this->isDone(),
            'date_created' => $this->getDateCreated()
        ]);
    }

    /**
     * Filter null values
     *
     * @param array $data
     * @return array
     */
    private function removeNullValues(array $data) : array
    {
        return array_filter($data, function ($value) {
            return !is_null($value);
        });
    }
}
