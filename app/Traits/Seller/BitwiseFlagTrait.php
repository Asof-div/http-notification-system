<?php
namespace App\Traits\Seller;

use App\Helpers\NotificationHelper;
use App\Models\Seller;

trait BitwiseFlagTrait
{
    protected $flags;

    /*
     * Note: these functions are protected to prevent outside code
     * from falsely setting BITS. See how the extending class 'User'
     * handles this.
     *
     */
    protected function isFlagSet($flag)
    {
        return (($this->flags & $flag) == $flag);
    }

    protected function setFlag($flag, $value)
    {
        if ($value) {
            $this->flags |= $flag;
        } else {
            $this->flags &= ~$flag;
        }
    }

    public function hasLogo()
    {
        $this->setFlagFromDb();
        return $this->isFlagSet(Seller::FLAG_HAS_LOGO);
    }

    public function hasTitle()
    {
        $this->setFlagFromDb();
        return $this->isFlagSet(Seller::FLAG_HAS_TITLE);
    }

    public function hasBody()
    {
        $this->setFlagFromDb();
        return $this->isFlagSet(Seller::FLAG_HAS_BODY);
    }

    public function hasMultimedia()
    {
        $this->setFlagFromDb();
        return $this->isFlagSet(Seller::FLAG_HAS_MULTIMEDIA);
    }

    public function hasSkill()
    {
        $this->setFlagFromDb();
        return $this->isFlagSet(Seller::FLAG_HAS_SKILL);
    }

    public function has3ServiceModules()
    {
        $this->setFlagFromDb();
        return $this->isFlagSet(Seller::FLAG_HAS_3_SERVICE_MODULES);
    }

    public function hasQualifications()
    {
        $this->setFlagFromDb();
        return $this->isFlagSet(Seller::FLAG_HAS_QUALIFICATIONS);
    }

    public function has12ServiceModules()
    {
        $this->setFlagFromDb();
        return $this->isFlagSet(Seller::FLAG_HAS_12_SERVICE_MODULES);
    }

    public function hasGallery()
    {
        $this->setFlagFromDb();
        return $this->isFlagSet(Seller::FLAG_HAS_GALLERY);
    }

    public function hasSummary()
    {
        $this->setFlagFromDb();
        return $this->isFlagSet(Seller::FLAG_HAS_SUMMARY);
    }

    public function setHasLogo($value)
    {
        $this->setFlagFromDb();
        $this->setFlag(Seller::FLAG_HAS_LOGO, $value);
        $this->saveFlagTODb();
    }

    public function setHasTitle($value)
    {
        $this->setFlagFromDb();
        $this->setFlag(Seller::FLAG_HAS_TITLE, $value);
        $this->saveFlagTODb();
    }

    public function setHasBody($value)
    {
        $this->setFlagFromDb();
        $this->setFlag(Seller::FLAG_HAS_BODY, $value);
        $this->saveFlagTODb();
    }

    public function setHasMultimedia($value)
    {
        $this->setFlagFromDb();
        $this->setFlag(Seller::FLAG_HAS_MULTIMEDIA, $value);
        $this->saveFlagTODb();
    }

    public function setHasSkill($value)
    {
        $this->setFlagFromDb();
        $this->setFlag(Seller::FLAG_HAS_SKILL, $value);
        $this->saveFlagTODb();
    }

    public function setHas3ServiceModules($value)
    {
        $this->setFlagFromDb();
        $this->setFlag(Seller::FLAG_HAS_3_SERVICE_MODULES, $value);
        $this->saveFlagTODb();
    }

    public function setHasQualifications($value)
    {
        $this->setFlagFromDb();
        $this->setFlag(Seller::FLAG_HAS_QUALIFICATIONS, $value);
        $this->saveFlagTODb();
    }

    public function setHas12ServiceModules($value)
    {
        $this->setFlagFromDb();
        $this->setFlag(Seller::FLAG_HAS_12_SERVICE_MODULES, $value);
        $this->saveFlagTODb();
    }

    public function setHasGallery($value)
    {
        $this->setFlagFromDb();
        $this->setFlag(Seller::FLAG_HAS_GALLERY, $value);
        $this->saveFlagTODb();
    }

    public function setHasSummary($value)
    {
        $this->setFlagFromDb();
        $this->setFlag(Seller::FLAG_HAS_SUMMARY, $value);
        $this->saveFlagTODb();
    }

    public function getFlag()
    {
        $this->setFlagFromDb();
        return $this->flags;
    }

    public function setFlagFromDb()
    {
        $this->flags = $this->percentage_profile_completion;
    }

    public function saveFlagTODb()
    {
        $this->percentage_profile_completion = $this->flags;
        $profileQuality = $this->getProfileCompletion();
        $searchInfo = $this->searchInfo;
        switch ($profileQuality) {
            case ($profileQuality >= 95):
                $result = 15;
                break;
            case ($profileQuality >= 90):
                $result = 8;
                break;
            case ($profileQuality >= 85):
                $result = 6;
                break;
            case ($profileQuality >= 80):
                $result = 4;
                break;
            default:
                $result = 0;
                break;
        }
        
        $searchInfo->update([
            'profile_quality' => $result,
        ]);
        $this->update([
            'percentage_profile_completion' => $this->flags,
            'profile_completion' => $profileQuality
        ]);
    }

    public function getProfileCompletion()
    {
        $this->setFlagFromDb();
        $profileCompletion = Seller::PERCENTAGE_PROFILE_COMPLETION_BASE;
        if ($this->hasTitle()) {
            $profileCompletion += 5;
        }
        //automatically add score for photo
        if ($this->hasMultimedia() || true) {
            $profileCompletion += 5;
        }
        if ($this->hasBody()) {
            $profileCompletion += 5;
        }
        if ($this->has3ServiceModules()) {
            $profileCompletion += 5;
        }
        if ($this->hasSkill()) {
            $profileCompletion += 5;
        }
        if ($this->hasQualifications()) {
            $profileCompletion += 5;
        }
        if ($this->hasGallery()) {
            $profileCompletion += 5;
        }
        if ($this->hasSummary()) {
            $profileCompletion += 5;
        }

        return $profileCompletion;
    }

    public function getProfileCompletionEligibility()
    {
        if ($this->hasTitle() && $this->hasBody() && $this->has3ServiceModules() && $this->hasSummary() && $this->hasSkill()) {
            if ($this->public_profile_status == 0 && $this->profile_ready == 0) {
                NotificationHelper::profileReady($this);
                $this->update(['profile_ready' => 1]);
            }
            return "true";
        } else {
            return "false";
        }
    }

}