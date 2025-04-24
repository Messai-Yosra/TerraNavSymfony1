<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DistanceConstraint extends Constraint
{
    public $message = 'La distance entre {{ departure }} et {{ destination }} dépasse 500 km. Veuillez choisir une destination plus proche.';
}