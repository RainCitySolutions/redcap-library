<?php
declare(strict_types = 1);
namespace RainCity\REDCap;


interface RedCapProjectFactoryIntf
{
    /**
     * Fetch a RedCapProject instance from the factory.
     *
     * @return RedCapProject A RedCapProject instance.
     */
    public function getProject(): RedCapProject;
}
