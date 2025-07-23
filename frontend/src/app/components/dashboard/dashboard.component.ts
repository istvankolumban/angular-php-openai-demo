import { Component } from '@angular/core';
import { AuthService } from '../../services/auth.service';
import { ChatComponent } from '../chat/chat.component';

@Component({
  selector: 'app-dashboard',
  imports: [ChatComponent],
  templateUrl: './dashboard.component.html',
  styleUrl: './dashboard.component.scss'
})
export class DashboardComponent {
  constructor(private authService: AuthService) {}

  logout(): void {
    this.authService.logout();
  }
}
