import { CanActivateFn, Router } from '@angular/router';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';
import { map, take, filter, timeout, catchError, switchMap } from 'rxjs/operators';
import { of } from 'rxjs';

export const guestGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  // Wait for auth check to complete, then check if user is authenticated
  return authService.authCheckCompleted$.pipe(
    filter(completed => completed), // Wait until auth check is complete
    take(1),
    switchMap(() => authService.isAuthenticated$.pipe(take(1))),
    timeout(5000), // Timeout after 5 seconds
    map(isAuthenticated => {
      if (isAuthenticated) {
        // User is authenticated, redirect to chat
        router.navigate(['/chat']);
        return false;
      } else {
        // User is not authenticated, allow access to login/register
        return true;
      }
    }),
    catchError(() => {
      // If timeout or error, allow access to login/register
      return of(true);
    })
  );
};
