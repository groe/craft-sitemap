<?php

namespace Craft;

abstract class Sitemap_ChangeFrequency extends BaseEnum {
    const Always  = 'always';

    const Daily   = 'daily';

    const Hourly  = 'hourly';

    const Monthly = 'monthly';

    const Never   = 'never';

    const Weekly  = 'weekly';

    const Yearly  = 'yearly';
}
