<?php

namespace App\Services\PFMEA;

use App\Repositories\PFMEA\PfmeaRepository;

class PfmeaService
{
    public function __construct(
        protected PfmeaRepository $pfmeaRepo
    ) {}

    public function searchData($search) {}

    public function getDataList() {}

    public function getDataById(int $id) {}

    public function store(array $data)
    {
        $insert = "";

        return $insert;
    }

    public function update(array $data, int $id) {}

    public function delete(int $id) {}

    public function massDelete(array $ids) {}
}
