import { Routes } from '@angular/router';
import { LoginComponent } from './components/auth/login/login.component';
import { RegisterComponent } from './components/auth/register/register.component';
import { DashboardComponent } from './components/dashboard/dashboard.component';
import { authGuard } from './guards/auth.guard';
import { guestGuard } from './guards/guest.guard';

export const routes: Routes = [
  { 
    path: '', 
    redirectTo: '/chat', 
    pathMatch: 'full' 
  },
  { 
    path: 'login', 
    component: LoginComponent, 
    canActivate: [guestGuard] 
  },
  { 
    path: 'register', 
    component: RegisterComponent, 
    canActivate: [guestGuard] 
  },
  { 
    path: 'chat', 
    component: DashboardComponent, 
    canActivate: [authGuard] 
  },
  { 
    path: '**', 
    redirectTo: '/chat' 
  }
];
