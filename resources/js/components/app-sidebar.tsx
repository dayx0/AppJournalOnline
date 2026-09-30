import { Link, usePage } from '@inertiajs/react';
import {
    Users,
    School,
    BookOpen,
    LayoutGrid,
    Shield,
    ClipboardCheck,
    ClipboardList,
    Bell,
    CalendarDays,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth, unreadCount, isWali } = usePage<any>().props;
    const isAdmin = auth?.user?.role === 'admin';
    const isMpk = auth?.user?.role === 'mpk';
    // Badge unread beneran; admin tidak punya antrean notifikasi.
    const notifBadge =
        isAdmin || !auth?.user ? 0 : ((unreadCount ?? 0) as number);

    const mainNavItems: NavItem[] = [
        ...(isMpk
            ? [
                  {
                      title: 'Dashboard MPK',
                      href: '/mpk/dashboard',
                      icon: LayoutGrid,
                  },
                  {
                      title: 'Validasi Jurnal',
                      href: '/mpk/jurnal',
                      icon: ClipboardCheck,
                  },
                  {
                      title: 'Notifikasi',
                      href: '/mpk/notifikasi',
                      icon: Bell,
                      badge: notifBadge,
                  },
                  {
                      title: 'Rekap',
                      href: '/mpk/rekap',
                      icon: ClipboardList,
                  },
              ]
            : [
                  {
                      title: 'Dashboard',
                      href: '/dashboard',
                      icon: LayoutGrid,
                  },
                  {
                      title: 'Jurnal',
                      href: '/jurnal',
                      icon: BookOpen,
                  },
                  // Menu Jadwal hanya untuk admin + wali kelas. Guru biasa
                  // melihat jadwal pribadinya lewat halaman Jurnal.
                  ...(isWali || isAdmin
                      ? [
                            {
                                title: 'Jadwal Pelajaran',
                                href: '/jadwal',
                                icon: CalendarDays,
                            },
                        ]
                      : []),
                  {
                      title: 'Notifikasi',
                      href: '/notifikasi',
                      icon: Bell,
                      badge: notifBadge,
                  },
              ]),
        ...(isAdmin
            ? [
                  {
                      title: 'Admin Dashboard',
                      href: '/admin/dashboard',
                      icon: Shield,
                  },
                  { title: 'Kelola User', href: '/admin/users', icon: Users },
                  { title: 'Kelola Kelas', href: '/admin/kelas', icon: School },
                  {
                      title: 'Kelola Mapel',
                      href: '/admin/mapel',
                      icon: BookOpen,
                  },
                  {
                      title: 'Monitor Jurnal',
                      href: '/admin/jurnal',
                      icon: BookOpen,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
