import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { map, take, filter, timeout, catchError, switchMap } from 'rxjs/operators';
import { of } from 'rxjs';

export const authGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  // Check if there's a token in localStorage
  const token = localStorage.getItem('auth_token');
  
  if (!token) {
    // No token, redirect to login immediately
    router.navigate(['/login']);
    return false;
  }

  // Token exists, wait for auth service to complete its check
  return authService.authCheckCompleted$.pipe(
    filter(completed => completed), // Wait until auth check is complete
    take(1),
    switchMap(() => authService.isAuthenticated$.pipe(take(1))),
    timeout(5000), // Timeout after 5 seconds
    map(isAuthenticated => {
      if (isAuthenticated) {
        return true;
      } else {
        // Token is invalid, redirect to login
        router.navigate(['/login']);
        return false;
      }
    }),
    catchError(() => {
      // If timeout or error, redirect to login
      router.navigate(['/login']);
      return of(false);
    })
  );
};
