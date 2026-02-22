<?php

namespace App\Enums;

enum BingoBattleInviteStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
}
