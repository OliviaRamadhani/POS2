import {
    createRouter,
    createWebHistory,
    type RouteRecordRaw,
} from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useConfigStore } from "@/stores/config";
import NProgress from "nprogress";
import "nprogress/nprogress.css";

declare module "vue-router" {
    interface RouteMeta {
        pageTitle?: string;
        permission?: string;
    }
}

const routes: Array<RouteRecordRaw> = [
    {
        path: "/",
        redirect: "/landing",
        component: () => import("@/layouts/default-layout/DefaultLayout.vue"),
        meta: {
            middleware: "auth",
        },
        
        children: [
            {
                path: "/dashboard",
                name: "dashboard",
                component: () => import("@/pages/dashboard/Index.vue"),
                meta: {
                    pageTitle: "Dashboard",
                    breadcrumbs: ["Dashboard"],
                },
            },
            {
                path: "/dashboard/profile",
                name: "dashboard.profile",
                component: () => import("@/pages/dashboard/profile/Index.vue"),
                meta: {
                    pageTitle: "Profile",
                    breadcrumbs: ["Profile"],
                },
            },
            {
                path: "/dashboard/setting",
                name: "dashboard.setting",
                component: () => import("@/pages/dashboard/setting/Index.vue"),
                meta: {
                    pageTitle: "Website Setting",
                    breadcrumbs: ["Website", "Setting"],
                },
            },

            // MASTER
            {
                path: "/dashboard/master/users/roles",
                name: "dashboard.master.users.roles",
                component: () => import("@/pages/dashboard/master/users/roles/Index.vue"),
                meta: {
                    pageTitle: "User Roles",
                    breadcrumbs: ["Master", "Users", "Roles"],
                },
            },
            {
                path: "/dashboard/master/users",
                name: "dashboard.master.users",
                component: () => import("@/pages/dashboard/master/users/Index.vue"),
                meta: {
                    pageTitle: "Users",
                    breadcrumbs: ["Master", "Users"],
                },
            },

            // Inventori
            {
                path: "/dashboard/inventori/produk",
                name: "dashboard.inventoriproduk",
                component: () => import("@/pages/dashboard/inventori/produk/index.vue"),
                meta: {
                    pageTitle: "Inventori Produk",
                    breadcrumbs: ["Inventori", "Produk"],
                },
            },
            {
                path: "/dashboard/inventori/laporan",
                name: "dashboard.inventorilaporan",
                component: () => import("@/pages/dashboard/inventori/laporan/index.vue"),
                meta: {
                    pageTitle: "Laporan Transaksi",
                    breadcrumbs: ["Inventori", "Laporan"],
                },
            },
            {
                path: "/dashboard/inventori/stok",
                name: "dashboard.inventoristok",
                component: () => import("@/pages/dashboard/inventori/stok/index.vue"),
                meta: {
                    pageTitle: "POS Items",
                    breadcrumbs: ["POS", "Items"],
                },
            },
            {
                path: "/dashboard/inventori/Reservation",
                name: "dashboard.inventori.reservation",
                component: () => import("@/pages/dashboard/inventori/Reservation/Index.vue"),
                meta: {
                    pageTitle: "Reservations",
                    breadcrumbs: ["Inventori", "Reservations"],
                },
            },

            // Kasir route added here
            {
                path: "/dashboard/kasir",
                name: "dashboard.kasir",
                component: () => import("@/pages/dashboard/kasir/index.vue"),
                meta: {
                    pageTitle: "Kasir",
                    breadcrumbs: ["Shop", "Kasir"],
                },
            },
        ],
    },
    {
        path: "/",
        component: () => import("@/layouts/AuthLayout.vue"),
        children: [
            {
                path: "/sign-in",
                name: "sign-in",
                component: () => import("@/pages/auth/sign-in/Index.vue"),
                meta: {
                    pageTitle: "Sign In",
                    middleware: "guest",
                },
            },
            {
                path: "/resets",
                name: "resets",
                component: () => import("@/pages/auth/resets/Index.vue"),
                meta: {
                    pageTitle: "Resets",
                    middleware: "guest",
                },
            },
        ],
    },

    {
        path: "/landing",
        redirect: "/landing/page",
        component: () => import("@/layouts/kasir/DefaultLayout.vue"),
        children: [
            {
                path: "/landing/page",
                name: "/landing.index",
                component: () => import("@/pages/landing/Index.vue"),
                meta: {
                    pageTitle: "Landing Index",
                },
            },
        ],
    },

    {
        path: "/landing/thai",
        name: "landingthai",
        component: () => import("@/pages/landing/thai.vue"),
        meta: {
            pageTitle: "Landing Thai",
        },
    },

    {
        path: "/landing/payment/:uuid",
        name: "landingcheck",
        component: () => import("@/pages/landing/payment.vue"),
        meta: {
            pageTitle: "Landing Payment",
        },
    },

    {
        path: "/",
        component: () => import("@/layouts/SystemLayout.vue"),
        children: [
            {
                path: "/404",
                name: "404",
                component: () => import("@/pages/errors/Error404.vue"),
                meta: {
                    pageTitle: "Error 404",
                },
            },
            {
                path: "/500",
                name: "500",
                component: () => import("@/pages/errors/Error500.vue"),
                meta: {
                    pageTitle: "Error 500",
                },
            },
        ],
    },
    {
        path: "/:pathMatch(.*)*",
        redirect: "/404",
    },
];

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
    scrollBehavior(to) {
        if (to.hash) {
            return {
                el: to.hash,
                top: 80,
                behavior: "smooth",
            };
        } else {
            return {
                top: 0,
                left: 0,
                behavior: "smooth",
            };
        }
    },
});

// Middleware
router.beforeEach(async (to, from, next) => {
    if (to.name) {
        NProgress.start();
    }

    const authStore = useAuthStore();
    const configStore = useConfigStore();

    if (to.meta.pageTitle) {
        document.title = `${to.meta.pageTitle} - ${import.meta.env.VITE_APP_NAME}`;
    } else {
        document.title = import.meta.env.VITE_APP_NAME as string;
    }

    configStore.resetLayoutConfig();

    if (!authStore.isAuthenticated) await authStore.verifyAuth();

    if (to.meta.middleware == "auth") {
        if (authStore.isAuthenticated) {
            if (to.meta.permission && !authStore.user.permission.includes(to.meta.permission)) {
                next({ name: "404" });
            } else {
                next();
            }
        } else {
            next({ name: "sign-in" });
        }
    } else if (to.meta.middleware == "guest" && authStore.isAuthenticated) {
        next({ name: "dashboard" });
    } else {
        next();
    }
});

router.afterEach(() => {
    NProgress.done();
});

export default router;
