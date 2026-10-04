import type { paths } from '~/types/api'

type TradableScoresResponse =
    paths['/api/v1/student/my/tradable-scores']['get']['responses'][200]['content']['application/json']

type MarketplaceProductsResponse =
    paths['/api/v1/student/marketplace/products']['get']['responses'][200]['content']['application/json']

export type MarketplaceProduct = NonNullable<MarketplaceProductsResponse['data']>[number]

type MarketplaceOrdersResponse =
    paths['/api/v1/student/marketplace/orders']['get']['responses'][200]['content']['application/json']

type MarketplaceSettingsResponse =
    paths['/api/v1/student/marketplace/settings']['get']['responses'][200]['content']['application/json']

export type MarketplaceOrder = NonNullable<MarketplaceOrdersResponse['data']>[number]

export type MarketplacePurchaseResponse =
    paths['/api/v1/student/marketplace/orders']['post']['responses'][201]['content']['application/json']

export function useStudentMarketplaceApi() {
    const { get, post } = useApi()

    async function getAvailableScore(): Promise<number> {
        const data = await get<TradableScoresResponse>('/api/v1/student/my/tradable-scores')
        return data.previous_semester_score ?? 0
    }

    async function getMarketplaceSettings(): Promise<{ pickup_hint: string }> {
        const data = await get<MarketplaceSettingsResponse>('/api/v1/student/marketplace/settings')
        return { pickup_hint: data?.data?.pickup_hint || '' }
    }

    async function getProducts(page: number): Promise<{
        products: MarketplaceProduct[]
        pagination: { currentPage: number; lastPage: number; total: number }
    }> {
        const params = { page }
        const data = await get<MarketplaceProductsResponse>('/api/v1/student/marketplace/products', { query: params })

        const products = Array.isArray(data.data) ? data.data : []

        return {
            products,
            pagination: {
                currentPage: data.current_page ?? 1,
                lastPage: data.last_page ?? 1,
                total: data.total ?? 0,
            },
        }
    }

    async function purchaseProduct(productId: number): Promise<MarketplacePurchaseResponse> {
        return await post<MarketplacePurchaseResponse>('/api/v1/student/marketplace/orders', {
            marketplace_id: productId,
        })
    }

    async function getOrders(
        page: number,
        status?: string | null,
        sort: string = 'created_at',
        direction: string = 'desc',
    ): Promise<{
        orders: MarketplaceOrder[]
        pagination: { currentPage: number; lastPage: number; total: number }
    }> {
        const params: Record<string, unknown> = { page, sort, direction }
        if (status) {
            params.status = status
        }
        const data = await get<MarketplaceOrdersResponse>('/api/v1/student/marketplace/orders', { query: params })

        const orders = Array.isArray(data.data) ? data.data : []

        return {
            orders,
            pagination: {
                currentPage: data.current_page ?? 1,
                lastPage: data.last_page ?? 1,
                total: data.total ?? 0,
            },
        }
    }

    return { getAvailableScore, getMarketplaceSettings, getProducts, purchaseProduct, getOrders }
}
