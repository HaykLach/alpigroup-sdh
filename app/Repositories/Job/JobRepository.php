<?php

declare(strict_types=1);

namespace App\Repositories\Job;

use App\Contracts\Job\JobRepositoryInterface;
use App\Models\Pim\Job\PimJob;
use App\Repositories\BaseRepository;
use Exception;

class JobRepository extends BaseRepository implements JobRepositoryInterface
{
    public function create(array $data): PimJob
    {
        return PimJob::create($data);
    }

    /**
     * @throws Exception
     */
    public function update(string $id, array $data): PimJob
    {
        $pimJob = $this->findModelByField(PimJob::class, $id, 'id');
        if (! $pimJob) {
            throw new Exception('Job with id '.$id.' not found!');
        }

        $pimJob->update($data);

        return $pimJob;
    }

    public function findByName(string $name): ?PimJob
    {
        return $this->findModelByField(PimJob::class, $name, 'job_name');
    }
}
