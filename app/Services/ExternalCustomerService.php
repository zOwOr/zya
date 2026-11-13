<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ExternalCustomer;
use Illuminate\Support\Facades\DB;

class ExternalCustomerService
{
    /**
     * Busca posibles coincidencias ya existentes en el catálogo de Clientes Internos
     * para evitar duplicados al registrar o promover un cliente externo.
     */
    public function findMatchingInternalCustomer(ExternalCustomer $external): ?Customer
    {
        $phone = trim((string) $external->tit_phone);
        if ($phone !== '') {
            $byPhone = Customer::where('tit_phone', $phone)->first();
            if ($byPhone) {
                return $byPhone;
            }
        }

        $email = mb_strtolower(trim((string) $external->tit_email));
        $name = mb_strtolower(trim((string) $external->tit_name));

        if ($email !== '' && $name !== '') {
            return Customer::whereRaw('LOWER(tit_email) = ?', [$email])
                ->whereRaw('LOWER(tit_name) = ?', [$name])
                ->first();
        }

        return null;
    }

    /**
     * Promueve o vincula un Cliente Externo al catálogo de Clientes Internos (`customers`)
     * preservando íntegro su historial previo vinculado mediante `external_customer_id`.
     *
     * @return array{outcome: string, customer: Customer, external_customer: ExternalCustomer}
     */
    public function promoteToInternal(
        ExternalCustomer $externalCustomer,
        ?int $actingUserId = null,
        bool $linkExistingOnDuplicate = false
    ): array {
        return DB::transaction(function () use ($externalCustomer, $actingUserId, $linkExistingOnDuplicate) {
            /** @var ExternalCustomer $lockedExternal */
            $lockedExternal = ExternalCustomer::where('id', $externalCustomer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedExternal->converted_customer_id) {
                $existingLinked = Customer::find($lockedExternal->converted_customer_id);
                if ($existingLinked) {
                    if ($lockedExternal->status !== ExternalCustomer::STATUS_CONVERTED) {
                        $lockedExternal->update(['status' => ExternalCustomer::STATUS_CONVERTED]);
                    }

                    return [
                        'outcome' => 'already_converted',
                        'customer' => $existingLinked,
                        'external_customer' => $lockedExternal->fresh(['convertedCustomer']),
                    ];
                }
            }

            if (!$lockedExternal->hasRequiredInternalFields()) {
                throw new \InvalidArgumentException(
                    'El cliente externo no cuenta con todos los campos requeridos del titular antes de promoverlo a cliente interno.'
                );
            }

            $matchedInternal = null;
            $phone = trim((string) $lockedExternal->tit_phone);
            if ($phone !== '') {
                $matchedInternal = Customer::where('tit_phone', $phone)->lockForUpdate()->first();
            }
            if (!$matchedInternal) {
                $matchedInternal = $this->findMatchingInternalCustomer($lockedExternal);
            }

            if ($matchedInternal) {
                if (!$linkExistingOnDuplicate) {
                    return [
                        'outcome' => 'duplicate_found',
                        'customer' => $matchedInternal,
                        'external_customer' => $lockedExternal,
                    ];
                }

                $lockedExternal->update([
                    'status' => ExternalCustomer::STATUS_CONVERTED,
                    'converted_customer_id' => $matchedInternal->id,
                    'converted_at' => now(),
                    'converted_by' => $actingUserId,
                ]);

                return [
                    'outcome' => 'linked_existing',
                    'customer' => $matchedInternal,
                    'external_customer' => $lockedExternal->fresh(['convertedCustomer']),
                ];
            }

            $customer = Customer::create($lockedExternal->toInternalCustomerAttributes());

            $lockedExternal->update([
                'status' => ExternalCustomer::STATUS_CONVERTED,
                'converted_customer_id' => $customer->id,
                'converted_at' => now(),
                'converted_by' => $actingUserId,
            ]);

            return [
                'outcome' => 'created',
                'customer' => $customer,
                'external_customer' => $lockedExternal->fresh(['convertedCustomer']),
            ];
        });
    }
}

