<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademyResource;
use App\Models\Academy;
use App\Models\EducationLevel;
use App\Models\Institution;
use App\Models\Planet;
use App\Models\Region;
use Illuminate\Http\JsonResponse;

/**
 * @group Public Endpoints
 *
 * @unauthenticated
 */
class PublicDataController extends Controller
{
    /**
     * Получение списка регионов
     */
    public function getRegions(): JsonResponse
    {
        return response()->json(Region::get());
    }

    /**
     * Получение списка направлений (планет)
     */
    public function getPlanets(): JsonResponse
    {
        return response()->json(Planet::get());
    }

    /**
     * Получение списка академий
     */
    public function getAcademies(): JsonResponse
    {
        $academies = Academy::orderBy('name')->get();

        return response()->json(AcademyResource::collection($academies)->resolve(request()));
    }

    /**
     * Получение списка уровней образования
     */
    public function getEducationLevels(): JsonResponse
    {
        return response()->json(EducationLevel::orderBy('name')->get());
    }

    /**
     * Получение списка учреждений
     */
    public function getInstitutions(): JsonResponse
    {
        return response()->json(Institution::orderBy('name')->get());
    }
}
