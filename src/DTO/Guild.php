<?php

namespace Kan\NkOpendata\DTO;

use Kan\NkOpendata\Hydrator\Attributes\MapFrom;

class Guild implements DTOInterface {
public function __construct(
    public string $name,
    public string $owner,
    public int $numMembers,
    public string $status,
    
    #[MapFrom('bannerURL')]
    public string $bannerUrl,
    
    #[MapFrom('frameURL')]
    public string $frameUrl,
    
    #[MapFrom('iconURL')]
    public string $iconUrl,
    
    public ?string $banner = null,
    public ?string $frame = null,
    public ?string $icon = null,
) {}
}