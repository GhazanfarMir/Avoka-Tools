<?php

namespace EburyLabs\Avoka\Forms;

use EburyLabs\Avoka\Contracts\Forms;

/**
 * Class CorporateClientsForm
 * @package EburyLabs\Avoka\Forms
 */
class CorporateClientsForm implements Forms
{
    /**
     * @var
     */
    protected $folderId;

    /**
     * @var
     */
    protected $formCode;

    /**
     * PrivateClientsForm constructor.
     * @param $formCode
     */
    public function __construct($formCode)
    {
        $this->formCode = $formCode;
    }

    /**
     * @return bool
     */
    public function getFormCode()
    {
        if (empty($this->formCode)) {
            return false;
        }

        return $this->formCode;
    }

    /**
     * @return bool
     */
    public function getFolderId()
    {
        if (empty($this->folderId)) {
            return false;
        }

        return $this->folderId;
    }

}